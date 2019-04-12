<?php

/**
 * Plugin Name: Advanced Custom Field Init
 * Description: Configuration for Advanced Custom Fields Pro
 * Author: NYC Opportunity
 */

add_action('acf/init', function() {

  // Enables the Google Maps API field
  // @url https://www.advancedcustomfields.com/resources/google-map/
  acf_update_setting('google_api_key', $_ENV['GOOGLE_MAPS_EMBED']);

});