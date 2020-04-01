<?php

namespace Controller;

/**
 * Dependencies
 */

use Timber;
use NYCO\Transients as Transients;

/**
 * Single Location Controller
 */
class SingleLocation extends Timber\Post {
  /**
   * Constructor
   * @return  $this
   */
  public function __construct() {
    parent::__construct();

    enqueue_language_style('style');
    enqueue_inline('rollbar');
    enqueue_inline('webtrends');
    enqueue_inline('data-layer');
    enqueue_inline('google-optimize');
    enqueue_inline('google-analytics');
    enqueue_inline('google-tag-manager');
    enqueue_script('main');

    return $this;
  }

  /**
   * Return the template for the location controller
   * @return [array] Array including the template string
   */
  public function templates() {
    return array(self::TEMPLATE);
  }

  /**
   * Fetches nearby stops using transient stops storied in the database
   * via the NYCO transients plugin.
   * @return [array] Collection of nearby stops.
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
   * @param  {object} el    The DOM Component with the data attr options
   * @param  {object} stops All of the stops data to compare to
   * @return {object}       A collection of the closest stops with distances
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
   * @param  {float} lat1 Latitude of point 1 (in decimal degrees)
   * @param  {float} lon1 Longitude of point 1 (in decimal degrees)
   * @param  {float} lat2 Latitude of point 2 (in decimal degrees)
   * @param  {float} lon2 Longitude of point 2 (in decimal degrees)
   * @return {float}      The distance in miles between each coordinate.
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
   * @param  {object} locations Object of closest locations
   * @return {object}           Same object with colors assigned to each loc
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
   * Constants
   */

  /** The transient to load */
  const TRANSIENT = 'subway_data';

  /** The template for the controller */
  const TEMPLATE = 'locations/single-location.twig';

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
