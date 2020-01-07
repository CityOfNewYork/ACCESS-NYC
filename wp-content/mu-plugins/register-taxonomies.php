<?php

// phpcs:disable
/**
 * Plugin Name: Register Taxonomies
 * Description: Adds custom taxonomies. Includes Programs, Outreach, Page-type, and Population Served.
 * Author: Blue State Digital
 */
// phpcs:enable

add_action('init', function() {
  // Creates the custom taxonomy taxonomy types we use for sorting/organizing programs
  register_taxonomy(
    'programs',
    'programs',
    array(
      'label' => __('Program Categories'),
      'labels' => array(
        'archives' => __('Programs', 'accessnyctheme')
      ),
      'hierarchical' => true,
      'public' => true, // will affect the terms rest route
      'show_in_rest' => true
    )
  );

  register_taxonomy(
    'page-type',
    'programs',
    array(
      'label' => __('Page Type'),
      'hierarchical' => true,
      'public' => false // will affect the terms rest route
    )
  );

  register_taxonomy(
    'populations-served',
    'programs',
    array(
      'label' => __('Population Served'),
      'labels' => array(
        'archives' => __('Population Served', 'accessnyctheme')
      ),
      'hierarchical' => true,
      'public' => true, // will affect the terms rest route
      'show_in_rest' => true
    )
  );
});
