<?php

/**
 * Routing
 */

// Locations
Routes::map('locations', function() {
  style();
  script('main');
  Routes::load('locations.php', null, null, 200);
});

Routes::map('locations/json', function() {
  Routes::load('archive-location.php', null, null, 200);
});

// Screener
Routes::map('eligibility', function() {
  style();
  script('main');
  Routes::load('screener.php', null, null, 200);
});

Routes::map('eligibility/results', function() {
  style();
  script('main');
  $params = array();
  $params['link'] = home_url().'/eligibility/results/';
  Routes::load('eligibility-results.php', $params, null, 200);
});

// Field Screener
Routes::map('peu', function() {
  style();
  script('main.field');
  Routes::load('screener-field.php', null, null, 200);
});

Routes::map('peu/results', function() {
  style();
  script('main.field');
  $params = array();
  $params['share_path'] = '/eligibility/results/';
  Routes::load('eligibility-results-field.php', $params, null, 200);
});

/**
 * Returns a shareable url and hash
 * @param  WP_REST_Request $request the request of the api
 * @return object                   the response
 */
function api_share_url(WP_REST_Request $request) {
  // Create the url
  $data = share_data($request->get_params());
  // Create the response object
  $response = new WP_REST_Response($data);
  // Add a custom status code
  $response->set_status(200);

  return $response;
}

/**
 * Function hook to initiate routes for the WP JSON Rest API
 */
function rest_routes() {
  register_rest_route('api/v1', '/shareurl/', array(
    'methods' => 'GET',
    'callback' => 'api_share_url'
  ));
}

add_action('rest_api_init', 'rest_routes');