<?php

// phpcs:disable
/**
 * Plugin Name: Register REST Routes
 * Description: Adds custom routes to the WP REST API. Some rest routes use WordPress Transients to cache requests to speed up requests (this applies to the terms in the Program Filters and Locations in the find help view). This means the caches need to be deleted for content to refresh. If the site is using the WordPress Object Cache on WP Engine use the cache clearing button in the WP Engine Admin page. If not using the WP Object cache, install the Transients Manager plugin to delete them. Developers can also delete the cache using the WP CLI.
 * Author: NYC Opportunity
 */
// phpcs:enable

use NYCO\WpAssets as WpAssets;

add_action('rest_api_init', function() {
  include_once ABSPATH . 'wp-admin/includes/plugin.php';

  /**
   * Configuration
   */

  $v = 'api/v1'; // namespace for the current version of the API
  $exp = HOUR_IN_SECONDS; // expiration of the transient caches
  $lang = (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) ?
    '_' . ICL_LANGUAGE_CODE : '';

  /**
   * Post types and terms that have corresponding transient caches
   * @var array
   */
  $transients = array(
    'location' => 'rest_locations_json' . $lang,
    'programs' => 'rest_terms_json' . $lang,
    'outreach' => 'rest_terms_json' . $lang,
    'page-type' => 'rest_terms_json' . $lang,
    'populations-served' => 'rest_terms_json' . $lang
  );

  /**
   * Register REST Routes
   */

  /**
   * Some rest endpoints use the WP Transient Cache to speed up the delivery
   * of results. Not all endpoints require the us of the cache, however. Check
   * the speed of the request to determine if it would benefit from the usage
   * of caching by looking at the Network panel. Generally, large payloads
   * with heavy operations or multiple queries will benefit from caching.
   *
   * At the bottom of the page are hooks for clearing the cache based on
   * certain actions such as saving posts or editing terms.
   */

  /**
   * Returns a list of public taxonomies and their terms. Public vs. private
   * is determined by the configuration in the Register Taxonomies plugin.
   */
  register_rest_route($v, '/terms/', array(
    'methods' => 'GET',
    'callback' => function(WP_REST_Request $request) use ($exp, $transients) {
      $transient = $transients['programs'];
      $data = get_transient($transient);

      if (false === $data) {
        $data = [];

        // Get public taxonomies and build our initial assoc. array
        foreach (get_taxonomies(array(
          'public' => true,
          '_builtin' => false
        ), 'objects') as $taxonomy) {
            $data[] = array(
              'name' => $taxonomy->name,
              'labels' => $taxonomy->labels,
              'taxonomy' => $taxonomy,
              'terms' => array()
            );
        }

        // Get the terms for each taxonomy
        $data = array_map(function ($tax) {
          $tax['terms'] = get_terms(array(
            'taxonomy' => $tax['name'],
            'hide_empty' => false,
          ));
          return $tax;
        }, $data);

        set_transient($transient, $data, $exp);
      }

      $response = new WP_REST_Response($data); // Create the response object
      $response->set_status(200); // Add a custom status code

      return $response;
    }
  ));
});
