<?php

/**
 * Dependencies
 */

use Config\Paths as Path;

/** Configuration */
require_once get_template_directory() . '/includes/_paths.php';
require_once Path\config('scripts');
require_once Path\config('styles');
require_once Path\config('routing');

/** Libraries */
require_once Path\lib('notifications');

/** Controllers */
require_once Path\controller('bsd-starter-site');
require_once Path\controller('locations');

/**
 * Initialization
 */

Notifications\timber();

/**
* Filter search to only show program page results
*/
if (!is_admin()) {
  add_filter('pre_get_posts', function ($query) {
    if ($query->is_search) {
      $query->set('post_type', 'programs');
    }
    return $query;
  });
}

/**
* Filter posts by multiple categories
*/
add_action('pre_get_posts', function ($query) {
  if (is_post_type_archive('programs') && !is_admin()) {
    $query->set('posts_per_page', 5);
  }

  if (
    ($query->is_home() || $query->is_search() || $query->is_archive()) &&
    $query->is_main_query() &&
    !is_admin()
  ) {
    $category_query = array();

    // Get the selected category, page type, or populations served, if any
    // Used in Programs Archive
    if (!empty($query->get('program_cat'))) {
      $category_query[] = array(
        'taxonomy' => 'programs',
        'field' => 'slug',
        'terms' => $query->get('program_cat'),
      );
    }

    // Used in Programs Single
    if (!empty($query->get('pop_served'))) {
      $category_query[] = array(
        'taxonomy' => 'populations-served',
        'field' => 'slug',
        'terms' => $query->get('pop_served'),
      );
    }

    // Used in Programs Single
    if (!empty($query->get('page_type'))) {
      $category_query[] = array(
        'taxonomy' => 'page-type',
        'field' => 'slug',
        'terms' => $query->get('page_type'),
      );
    }

    if (!empty($category_query)) {
      $category_query['relation'] = 'AND';
      $query->set('tax_query', $category_query);
    }
  }
});

// *****
// modifying program url in the language switcher
add_filter('icl_ls_languages', function($languages) {
  global $sitepress;

  if (isset($_GET['program_cat'])) {
    $cur_prog = $_GET['program_cat'];
    $original_lang = ICL_LANGUAGE_CODE; // Save the current language

    // switch to english to capture the original taxonomies
    if ($original_lang != 'en') {
      $sitepress->switch_lang('en');
    }

    // retrieve the program taxonomies as array
    $terms = get_terms(array(
      'taxonomy' => 'programs',
      'hide_empty' => false,
    ));

    // switch back to the original language
    $sitepress->switch_lang($original_lang);

    // find the en taxonomy that matches the current program
    foreach ($terms as $term) {
      if (strpos($cur_prog, $term->slug) !== false) {
        $prog = $term->slug;
      }
    }

    // reconstruct the language url based on the program filter
    if (strpos(basename($_SERVER['REQUEST_URI']), 'program_cat') !== false) {
      foreach ($languages as $lang_code => $language) {
        if ($lang_code == 'en') {
          $newlang_code = "";
          $languages[$lang_code]['url'] = '/programs/?program_cat=' . $prog;
        } elseif ($lang_code != 'en' || $lang_code != '') {
          // if not english, then remove the language code and add the correct one
          $languages[$lang_code]['url'] = '/' . $lang_code .
          '/programs/?program_cat=' . $prog . '-' . $lang_code;
        }
      }
    }
  }

  return $languages;
});
// end modifying url
// *****

// Define site.
new BsdStarterSite();

// *****
// Function to trigger Google Maps render on content import
global $office_loc;
$office_loc = array(
  'post_types' => array('location'),
  'fields' => array(
    'google_map' => 'field_588003b6be759',
    'address_street'   => 'field_58800318be754',
    'address_street_2' => 'field_5880032abe755',
    'city'      => 'field_58acf5f524f67',
    'zip'       => 'field_58acf60c24f68',
  ),
);

/**
 * Trigger Google map locations to properly update in /locations posts
 */
add_action('wp', function() {
  global $post;
  global $office_loc;

  if ($post && $post->post_type == 'location') {
    // get the address field for the google map
    $location = get_field($office_loc['fields']['google_map'], $post->ID);

    // check to see if the address is empty - if empty, populated it with the correct fields
    if (isset($location['address']) && isset($location['lat']) && isset($location['lng'])) {
      $full_address = $location['address'];
    } else {
      // create a location array to be populated
      $location = array(
        'address' => '',
        'lat' => '',
        'lng' => ''
      );

      // create a full address
      $full_address = get_field(
        $office_loc['fields']['address_street'], $post->ID) . ', ' .
        get_field($office_loc['fields']['city'], $post->ID) . ' ' .
        get_field($office_loc['fields']['zip'], $post->ID
      );

      $address = urlencode($full_address); // Spaces as + signs

      // will want to replace the key
      $key = get_env('GOOGLE_MAPS_EMBED');
      $address_query = wp_remote_get("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=$key");
      $address_json = wp_remote_retrieve_body($address_query);
      $address_data = json_decode($address_json);

      // if the address contains info
      if (isset($address_data)) {
        echo var_dump($address_data);
        $lat = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $lng = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
      }
      // set the new address
      $location['address'] = $full_address;
      $location['lat'] = $lat;
      $location['lng'] = $lng;

      // update the address, latitude, and longitude field
      update_post_meta($post->ID, 'address', $location['address']);
      update_post_meta($post->ID, 'lat', $location['lat']);
      update_post_meta($post->ID, 'lng', $location['lng']);

      // update the google map fields
      update_field($office_loc['fields']['google_map'], $location, $post->ID);
    }
  }
}, 1);

// GatherContent - Mapped WordPress Field meta_keys edit
add_filter('gathercontent_importer_custom_field_keys', function ($meta_keys) {
  // empty array that will contain the unique meta keys for the mapped fields
  $new_meta_keys = array();

  // Creates a new array of meta_keys that are not prefixed with underscore
  foreach ($meta_keys as $key => $value) {
    if (substr($value, 0, 1) != '_') {
      $new_meta_keys[$key] = $value;
    }
  }
  // return the new array
  return $new_meta_keys;
});
// end of GatherContent - Mapped WordPress Field meta_keys edit

/**
 * Validate params through regex
 * @param  string $namespace - the namespace of the parameter
 * @param  string $subject   - the string to validate
 * @return string            - returns blank string if false, parameter if valid
 */
function validate_params($namespace, $subject) {
  $patterns = array(
    'programs' => '/^[A-Z0-9,]*$/',
    'categories' => '/^[a-z,-]*$/',
    'date' => '/^[0-9]*$/',
    'guid' => '/^[a-zA-Z0-9]{13,13}$/',
    'step' => '/^[a-z,-]*$/'
  );
  preg_match($patterns[$namespace], $subject, $matches);
  return (isset($matches[0])) ? $matches[0] : ''; // fail silently
}

/**
 * Creates a shareable url along with valid hash
 * @param  array $params - requires programs, categories, date, guid, share_link
 * @return array         - 0; the url 1; the hash
 */
function share_data($params) {
  $query = array();
  $data = array();

  // Gets the URL Parameters for the search value,
  if (isset($params['programs'])) {
    $query['programs'] = validate_params(
      'programs', urldecode(htmlspecialchars($params['programs']))
    );
  }

  if (isset($params['categories'])) {
    $query['categories'] = validate_params(
      'categories', urldecode(htmlspecialchars($params['categories']))
    );
  }

  if (isset($params['date'])) {
    $query['date'] = validate_params(
      'date', urldecode(htmlspecialchars($params['date']))
    );
  }

  if (isset($params['guid'])) {
    $query['guid'] = validate_params(
      'guid', urldecode(htmlspecialchars($params['guid']))
    );
  }

  // Build query
  $http_query = http_build_query($query);
  $http_query = (isset($http_query)) ? '?'.$http_query : '';
  $url = home_url().$params['path'].$http_query;
  $hash = \SMNYC\hash($url);

  return array('url' => $url, 'hash' => $hash, 'query' => $query);
}

/**
 * Get the environment variable from config
 * @param  string $value The key for the environment variable
 * @return string        The environment variable
 */
function get_env($value) {
  return $_ENV[$value];
}
