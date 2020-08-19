<?php

/**
 * Locations
 *
 * @link /wp-json/wp/v2/location
 * @author NYC Opportunity
 */

namespace Controller;

use Timber;
use NYCO\Transients as Transients;

class Location extends Timber\Post {
  /**
   * Constructor
   * @return  $this
   */
  public function __construct($pid = false) {
    if ($pid) {
      parent::__construct($pid);
    } else {
      parent::__construct();
    }

    $this->get_help = $this->getHelp();

    return $this;
  }

  /**
   * Items returned in this object determine what is shown in the WP REST API.
   * Called by 'rest-prepare-posts.php' must use plugin.
   *
   * @return  Array  Items to show in the WP REST API
   */
  public function showInRest() {
    return array(
      'nearby_stops' => $this->nearbyStops()
    );
  }

  /**
   * Returns the phone number for the location shown.
   *
   * @return  Array  Phone number and extension.
   */
  public function getPhone() {
    $phone = $this->get_field('phone');
    return $phone;
  }

  /**
   * Returns the URL to a map of the location.
   *
   * @return  String  URL of the map location.
   */
  public function locationMapURL() {
    $mapUrl = 'https://www.google.com/maps/dir//';
    $address = $this->address['address'];
    return $mapUrl .= $address;
  }

  /**
   * Returns the type of the location.
   *
   * @return  String  type of location.
   */
  public function locationType() {
    $locationType = ($this->type === 'Government Office') ? 'GovernmentOffice' : 'Organization';
    return $locationType;
  }

  /**
   * Get the page meta description.
   *
   * @return  String
   */
  public function getPageMetaDescription() {
    return $this->getHelp();
  }

  /**
   * Get the line of text regarding what this location can help with.
   *
   * @return  String/Boolean  String if programs are available. False if not.
   */
  public function getHelp() {
    if ($this->custom['programs']) {
      return __('This location can help you with:', 'accessnyc-locations') .
        ' ' . implode(', ', $this->get_field('programs')) . '.';
    } else {
      return false;
    }
  }

  /**
   * Fetches nearby stops using transient stops storied in the database
   * via the NYCO transients plugin.
   *
   * @return  Array  Collection of nearby stops.
   */
  public function nearbyStops() {
    if (!class_exists('NYCO\Transients')) {
      return false;
    }

    $locations = self::nearbyStopsLocate(
      $this->address['lat'], // the lat and lng of the location post
      $this->address['lng'] // the lat and lng of the location post
    );

    if (false === $locations) {
      return $locations;
    }

    $locations = self::nearbyStopsColors($locations);

    return $locations;
  }

  /**
   * This compares the latitude and longitude with the Subway Stops data, sorts
   * the data by distance from closest to farthest, and returns the stop and
   * distances of the stations.
   *
   * @param   Number  $lat  Latitude of location
   * @param   Number  $lon  Longitude of location
   *
   * @return  Array         A collection of the closest stops with distances
   */
  private function nearbyStopsLocate($lat, $lon) {
    $geo = [];
    $distances = [];
    $stops = Transients::get(self::TRANSIENT);
    $skey = 'distance';

    // The WP Transients API will return false if empty
    if (false === $stops) {
      return $stops;
    }

    // 1. Compare lat and lon of current location with list of stops
    foreach ($stops as $i => $stop) {
      $geo = $stop[self::KEYS['ODATA_GEO']][self::KEYS['ODATA_COOR']];
      $geo = array_reverse($geo);
      array_push($distances, [
        'distance' => self::equirectangular($lat, $lon, $geo[0], $geo[1]),
        'stop' => $i, // index of stop in the data
      ]);
    }

    // 2. Sort the distances shortest to longest
    usort($distances, function ($a, $b) {
      return ($a['distance'] < $b['distance']) ? -1 : 1;
    });

    $distances = array_slice($distances, 0, self::NEARBY_STOPS_AMOUNT);

    // 3. Return the list of closest stops (number based on Amount option)
    //    and replace the stop index with the actual stop data
    foreach ($distances as $i => $dist) {
      $distances[$i]['stop'] = $stops[$dist['stop']];
    }

    return $distances;
  }

  /**
   * Returns distance in miles comparing the latitude and longitude of two
   * points using decimal degrees.
   *
   * @param   Float  lat1  Latitude of point 1 (in decimal degrees)
   * @param   Float  lon1  Longitude of point 1 (in decimal degrees)
   * @param   Float  lat2  Latitude of point 2 (in decimal degrees)
   * @param   Float  lon2  Longitude of point 2 (in decimal degrees)
   *
   * @return  Float        The distance in miles between each coordinate.
   */
  private function equirectangular($lat1, $lon1, $lat2, $lon2) {
    $alpha = abs($lon2) - abs($lon1);
    $x = deg2rad($alpha) * cos(deg2rad($lat1 + $lat2) / 2);
    $y = deg2rad($lat1 - $lat2);
    $R = 3959; // earth radius in miles;
    $distance = sqrt($x * $x + $y * $y) * $R;

    return $distance;
  }

  /**
   * Assigns trunk slugs to the data using the TRUNKS dictionary.
   *
   * @param   Array  locations  Object of closest locations
   *
   * @return  Array             Same object with colors assigned to each loc
   */
  private function nearbyStopsColors($locations) {
    $trunk = 'shuttles';

    // Loop through each location that we are going to display
    foreach ($locations as $i => $location) {
      // Assign the line to a variable to lookup in our color dictionary
      $location_lines = explode('-', $location['stop'][self::KEYS['ODATA_LINE']]);

      foreach ($location_lines as $x => $line) {
        foreach (self::TRUNKS as $trunk) {
          // Look through each color in the color dictionary
          if (in_array($line, $trunk['LINES'])) {
            $location_lines[$x] = array(
              'line' => $line,
              'trunk' => $trunk['TRUNK']
            );
          }
        }
      }

      // Add the trunk to the location
      $locations[$i]['trunks'] = $location_lines;
    }

    return $locations;
  }

  /**
   * Get structured data for this location
   *
   * @return  Array  Structured data object
   */
  public function getSchema() {
    return array(
      '@context' => 'https://schema.org',
      '@type' => $this->locationType(),
      'name' => $this->title,
      'hasMap' => $this->locationMapURL(),
      'description' => $this->getHelp(),
      'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => $this->address_street,
        'addressLocality' => $this->city,
        'postalCode' => $this->zip
      ),
      'telephone' => $this->getPhone(),
      'sameAs' => $this->website,
      'spatialCoverage' => array(
        'type' => 'City',
        'name' => 'New York'
      )
    );
  }

  /**
   * Constants
   */

  /** The transient to load */
  const TRANSIENT = 'subway_data';

  /** The template for the controller */
  // const TEMPLATE = 'locations/single-location.twig';

  /** The amount of nearby stops to show */
  const NEARBY_STOPS_AMOUNT = 3;

  /** Keys that are used to reference external data */
  const KEYS = array(
    'ODATA_GEO' => 'the_geom',
    'ODATA_COOR' => 'coordinates',
    'ODATA_LINE' => 'line'
  );

  /**  */
  const TRUNKS = array(
    [
      'TRUNK' => 'eighth-avenue',
      'LINES' => ['A', 'C', 'E'],
    ],
    [
      'TRUNK' => 'sixth-avenue',
      'LINES' => ['B', 'D', 'F', 'M'],
    ],
    [
      'TRUNK' => 'crosstown',
      'LINES' => ['G'],
    ],
    [
      'TRUNK' => 'canarsie',
      'LINES' => ['L'],
    ],
    [
      'TRUNK' => 'nassau',
      'LINES' => ['J', 'Z'],
    ],
    [
      'TRUNK' => 'broadway',
      'LINES' => ['N', 'Q', 'R', 'W'],
    ],
    [
      'TRUNK' => 'broadway-seventh-avenue',
      'LINES' => ['1', '2', '3'],
    ],
    [
      'TRUNK' => 'lexington-avenue',
      'LINES' => ['4', '5', '6', '6 Express'],
    ],
    [
      'TRUNK' => 'flushing',
      'LINES' => ['7', '7 Express'],
    ],
    [
      'TRUNK' => 'shuttles',
      'LINES' => ['S']
    ]
  );
}
