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
  include_once WPMU_PLUGIN_DIR . '/rest/Auth.php';

  /**
   * Configuration
   */

  $v = 'api/v1'; // namespace for the current version of the API
  $exp = WEEK_IN_SECONDS; // expiration of the transient caches
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
   * Returns a shareable url and hash for the Send Me NYC plugin. This is used
   * when program results of the PEU Screener are modified. It relies on a
   * function in functions.php, share_data()
   */
  register_rest_route($v, '/shareurl/', array(
    'methods' => 'GET',
    'permission_callback' => [REST\Auth::class, 'smnycToken'],
    'callback' => function (WP_REST_Request $request) {
      $params = $request->get_params();
      unset($params['url']);

      // Create the url, share_data -> functions.php
      $data = share_data($request->get_params());

      // Create the response object and status code
      $response = new WP_REST_Response($data);
      $response->set_status(200);

      return $response;
    }
  ));

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

  /**
   * Returns locations posts in JSON format. This endpoint was refactored
   * from a custom endpoint written for the first release of this site. It
   * is used by the /locations view to display locations content and the
   * locations map.
   *
   * That feature could have used the default WordPress REST endpoint
   * http://localhost:8080/wp-json/wp/v2/location However, they wanted to
   * optimize the loading time, but, that request is not any faster due to
   * the size of the response. It would take a bit of refactoring to update
   * the feature with pagination, etc. For now this returns the expected data
   * for the application to parse. It also uses the WordPress Transient
   * Cache to speed up the request.
   */
  register_rest_route($v, '/locations/', array(
    'methods' => 'GET',
    'callback' => function(WP_REST_Request $request) use ($transients, $exp) {
      $transient = $transients['location'];
      $data = get_transient($transient);

      if (false === $data) {
        global $sitepress;
        $default_lang = $sitepress->get_default_language();
        $data = [];

        $posts = get_posts(array(
          'post_type' => 'location',
          'numberposts' => -1,
          'suppress_filters' => 0
        ));

        foreach ($posts as $post) {
          $programs = [];
          $related_programs = get_field('programs', $post->ID);

          if ($related_programs) {
            foreach ($related_programs as $program) {
              array_push(
                $programs,
                icl_object_id($program->ID, 'post', true, $default_lang)
              );
            }
          }

          $data[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'programs' => $programs,
            'address' => array(
              'street' => $post->address['address'],
              'lat' => $post->address['lat'],
              'lng' => $post->address['lng']
            ),
            'type' => $post->type,
            'link' => get_permalink($post)
          );
        }

        $bool = set_transient($transient, $data, $exp);
      }

      $response = new WP_REST_Response($data);
      $response->set_status(200);

      return $response;
    }
  ));

  /**
   * Use NYCO WP Assets to build rest routes for inline scripts.
   */
  // $WpAssets = new WpAssets();
  // $path = WPMU_PLUGIN_DIR . '/integrations/integrations.json';
  // $integrations = json_decode(file_get_contents($path), true);

  // $WpAssets->registerRestRoutes($integrations);
});
