<?php

/**
 * Plugin Name: Routes
 * Description: Misc. programatic routes via Timber Routes.
 * Author: NYC Opportunity
 */

use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

/**
 * Add Filter after all plugins are loaded to make sure we have our dependencies.
 */

add_filter('plugins_loaded', function() {
  if (!class_exists('Routes')) {
    return;
  }

  /**
   * Create an endpoint for CSP Reporting to post to Rollbar or the default
   * error log. Rollbar requires the Rollbar WordPress plugin or the Rollbar
   * PHP library. The default log requires DEBUG_MODE to be set to true and
   * DISPLAY_ERRORS to be set to false.
   */

  if (defined('WP_HEADERS_CSP_REPORTING') && WP_HEADERS_CSP_REPORTING) {
    Routes::map(WP_HEADERS_CSP_REPORTING, function() {
      http_response_code(204);

      if (class_exists('Rollbar\Rollbar')) {
        Rollbar::log(Level::INFO, 'CSP Reporting', json_decode(file_get_contents('php://input')));
      } else {
        error_log(file_get_contents('php://input')); // Default error log
      }

      exit();
    });
  }
});
