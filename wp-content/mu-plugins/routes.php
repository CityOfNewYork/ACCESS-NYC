<?php

/**
 * Plugin Name: Routes
 * Description: Misc. programatic routes via Timber Routes.
 * Author: NYC Opportunity
 */

add_filter('plugins_loaded', function() {
  if (!class_exists('Routes')) {
    return;
  }

  /**
   * Create an endpoint for CSP Reporting.
   */
  if (defined('WP_HEADERS_CSP_REPORTING') && WP_HEADERS_CSP_REPORTING) {
    Routes::map(WP_HEADERS_CSP_REPORTING, function() {
      http_response_code(204);

      error_log(file_get_contents('php://input'));

      exit();
    });
  }
});