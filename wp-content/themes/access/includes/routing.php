<?php

/**
 * Routing
 */

// Locations
Routes::map('locations', function () {
  Routes::load('locations.php', null, null, 200);
});

Routes::map('locations/json', function () {
  Routes::load('archive-location.php', null, null, 200);
});

Routes::map('stops/json', function () {
  Routes::load('archive-location.php', null, null, 200);
});

// Screener
Routes::map('eligibility', function () {
  Routes::load('screener.php', null, null, 200);
});

Routes::map('eligibility/results', function () {
  $params = array();
  $params['link'] = home_url().'/eligibility/results/';
  Routes::load('screener-results.php', $params, null, 200);
});

// Field Screener
Routes::map('peu', function () {
  Routes::load('field.php', null, null, 200);
});

Routes::map('peu/results', function () {
  $params = array();
  $params['share_path'] = '/eligibility/results/';
  Routes::load('field-results.php', $params, null, 200);
});

/**
 * REST API
 */
add_action('rest_api_init', function () {
  $v = 'api/v1';

  register_rest_route($v, '/shareurl/', array(
    'methods' => 'GET',
    /**
     * Returns a shareable url and hash
     * @param  WP_REST_Request $request the request of the api
     * @return object                   the response
     */
    'callback' => function (WP_REST_Request $request) {
      $data = share_data($request->get_params()); // Create the url, share_data -> functions.php
      $response = new WP_REST_Response($data); // Create the response object
      $response->set_status(200); // Add a custom status code

      return $response;
    }
  ));

  register_rest_route($v, '/terms/', array(
    'methods' => 'GET',
    /**
     * Returns a list of public taxonomies and their terms
     * @param  WP_REST_Request $request the request of the api
     * @return object                   the response
     */
    'callback' => function (WP_REST_Request $request) {
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

      $response = new WP_REST_Response($data); // Create the response object
      $response->set_status(200); // Add a custom status code
      return $response;
    }
  ));
});
