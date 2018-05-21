<?

/**
 * Plugin Name: Custom Post Types and Taxonomies
 * Description: Homepage, Homepage Touts, Programs, Locations, Program Search Links, Alerts
 * Author: Blue State Digital
 */

// Define custom post types.
function bsd_custom_post_type() {
  register_post_type( 'homepage',
    array(
      'labels' => array(
        'name' => __( 'Homepage' ),
        'singular_name' => __( 'Homepage' ),
        'all_items' => __( 'All Pages' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add Homepage' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Homepage' ),
        'new_item' => __( 'New Homepage' ),
        'view_item' => __( 'View Homepage' ),
        'search_items' => __( 'Search Homepages' ),
      ),
      'description' => __( 'Homepage.' ),
      'public' => true,
      'exclude_from_search' => true,
      'show_ui' => true,
      'hierarchical' => false,
      'supports' => array( 'title' ),
      'menu_icon' => 'dashicons-admin-home'
    )
  );

  register_post_type( 'homepage_tout',
    array(
      'labels' => array(
        'name' => __( 'Homepage Touts' ),
        'singular_name' => __( 'Homepage Touts' ),
        'all_items' => __( 'All Touts' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add Homepage Tout' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Tout' ),
        'new_item' => __( 'New Tout' ),
        'view_item' => __( 'View Tout' ),
        'search_items' => __( 'Search Touts' ),
      ),
      'description' => __( 'Homepage "did you know" announcements.' ),
      'public' => true,
      'exclude_from_search' => true,
      'show_ui' => true,
      'hierarchical' => false,
      'supports' => array( 'title' ),
      'menu_icon' => 'dashicons-megaphone'
    )
  );

  register_post_type( 'programs',
    array(
      'labels' => array(
        'name' => __( 'Programs' ),
        'singular_name' => __( 'Program' ),
        'all_items' => __( 'All Programs' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New Program' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Program' ),
        'new_item' => __( 'New Program' ),
        'view_item' => __( 'View Program' ),
        'search_items' => __( 'Search Programs' ),
      ),
      'rewrite' => array( 'slug' => 'programs', 'with_front' => false ),
      'description' => __( 'A program featured on the site.' ),
      'public' => true,
      'exclude_from_search' => false,
      'show_ui' => true,
      'taxonomies' => array('populations-served', 'programs', 'page-type'),
      'hierarchical' => false,
      'supports' => array( 'title' ),
      'menu_icon' => 'dashicons-format-aside',
      'has_archive' => true
    )
  );

  register_post_type( 'location',
    array(
      'labels' => array(
        'name' => __( 'Locations' ),
        'singular_name' => __( 'Location' ),
        'all_items' => __( 'All Locations' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New Location' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Location' ),
        'new_item' => __( 'New Location' ),
        'view_item' => __( 'View Location' ),
        'search_items' => __( 'Search Locations' ),
      ),
      'description' => __( 'Map locations' ),
      'public' => true,
      'exclude_from_search' => false,
      'show_ui' => true,
      'show_in_rest' => true,
      'hierarchical' => false,
      'supports' => array( 'title', 'thumbnail', 'editor', 'slug' ),
      'capability_type' => 'post',
      'menu_icon' => 'dashicons-location'
    )
  );

  register_post_type( 'program_search_links',
    array(
      'labels' => array(
        'name' => __( 'Program Search Links' ),
        'singular_name' => __( 'Program Search Link' ),
        'all_items' => __( 'All Program Search Links' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add Program Search Link' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Program' ),
        'new_item' => __( 'New Program' ),
        'view_item' => __( 'View Program' ),
        'search_items' => __( 'Search Programs' ),
      ),
      'description' => __( 'Search Drawer Links to other Programs.' ),
      'public' => true,
      'exclude_from_search' => true,
      'show_ui' => true,
      'hierarchical' => false,
      'supports' => array( 'title' ),
      'menu_icon' => 'dashicons-feedback'
    )
  );

  register_post_type( 'alert',
    array(
      'labels' => array(
        'name' => __( 'Site Alerts' ),
        'singular_name' => __( 'Alert' ),
        'all_items' => __( 'All Alerts' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New Alert' ),
        'edit' => __( 'Edit' ),
        'edit_item' => __( 'Edit Alert' ),
        'new_item' => __( 'New Alert' ),
        'view_item' => __( 'View Alert' ),
        'search_items' => __( 'Search Alerts' ),
      ),
      'description' => __( 'A site alert.' ),
      'public' => true,
      'exclude_from_search' => true,
      'show_ui' => true,
      'hierarchical' => false,
      'supports' => array( 'title', 'thumbnail' ),
      'menu_icon' => 'dashicons-format-quote'
    )
  );

  // Creates the custom taxonomy taxonomy types we use for
  // sorting/organizing programs
  register_taxonomy(
    'programs',
    'programs',
    array(
      'label' => __( 'Program Categories' ),
      'hierarchical' => true,
    )
  );

  register_taxonomy(
    'outreach',
    'programs',
    array(
      'label' => __('Outreach Categories'),
      'hierarchical' => true,
    )
  );

  register_taxonomy(
    'page-type',
    'programs',
    array(
      'label' => __( 'Page Type' ),
      'hierarchical' => true,
    )
  );

  register_taxonomy(
    'populations-served',
    'programs',
    array(
      'label' => __( 'Populations Served' ),
      'hierarchical' => true,
    )
  );

}

add_action( 'init', 'bsd_custom_post_type');

