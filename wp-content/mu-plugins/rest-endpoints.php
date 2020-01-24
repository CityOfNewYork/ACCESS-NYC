<?php

/**
 * Plugin Name: Disable Rest Endpoints
 * Description: Explicityly disables endpoints related to users.
 * Author: NYC Opportunity
 */

add_filter('rest_endpoints', function($endpoints) {
  $disable = [
    '/wp/v2/users',
    '/wp/v2/users/me',
    '/wp/v2/users/(?P<id>[\d]+)',
    '/acf/v3/users',
    '/acf/v3/users/(?P<id>[\\d]+)/?(?P<field>[\\w\\-\\_]+)?'
  ];

  foreach ($disable as $key => $endpoint) {
    if (isset($endpoints[$endpoint])) {
      unset($endpoints[$endpoint]);
    }
  }

  return $endpoints;
});
