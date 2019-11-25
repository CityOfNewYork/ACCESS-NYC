<?php

/**
 * Plugin Name: Advanced Custom Field Init
 * Description: Configuration for Advanced Custom Fields Pro. Currently, it adds the 'google_api_key' to ACF settings.
 * Author: NYC Opportunity
 */

add_action('acf/init', function() {
  // Enables the Google Maps API field
  // @url https://www.advancedcustomfields.com/resources/google-map/
  if (defined('GOOGLE_LOCATION_POST_IMPORT')) {
    acf_update_setting('google_api_key', GOOGLE_LOCATION_POST_IMPORT);
  }
});
