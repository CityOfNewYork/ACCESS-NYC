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

  /**
   * Taxonomy: Agency
   *
   * @author NYC Opportunity
   */
  register_taxonomy('agency', ['programs', 'location'], array(
    'label' => __('Agency', 'accessnyctheme'),
    'labels' => array(
      'name' => __('Agencies', 'accessnyctheme'),
      'singular_name' => __('Agency', 'accessnyctheme'),
    ),
    'public' => false, // Will determine if shown in archive pages
    'publicly_queryable' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'query_var' => true,
    'rewrite' => array(
      'slug' => 'agencies',
      'with_front' => true
    ),
    'show_admin_column' => false,
    'show_in_rest' => true,
    'show_tagcloud' => true,
    'rest_base' => 'agencies',
    'rest_controller_class' => 'WP_REST_Terms_Controller',
    'show_in_quick_edit' => false,
    'show_in_graphql' => false
  ));
});
