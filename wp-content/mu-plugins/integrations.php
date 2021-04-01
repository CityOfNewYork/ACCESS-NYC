<?php

/**
 * Plugin Name: Create Integrations Settings Page
 * Description: Add an Option Page for configuring integrations.
 * Plugin URI: https://github.com/cityofnewyork/access-nyc
 * Author: NYC Opportunity
 * Author URI: nyc.gov/opportunity
 */
add_action('acf/init', function() {
  // Check function exists.
  if (function_exists('acf_add_options_page')) {
    // Add parent.
    $parent = acf_add_options_page(array(
      'page_title'  => __('Integrations'),
      'menu_title'  => __('Integrations'),
      'redirect'    => false,
      'position'    => '79'
    ));
  }
});
