<?php

if ( ! class_exists( 'Timber' ) ) {
  add_action( 'admin_notices', function() {
    echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
  } );
  return;
}

// Disable the google-authenticator plugin for local and staging environments.
// is_wpe is defined in the mu-plugins/wpengine-common plugin. is_wpe()
// returns true only if the site is running on a production environment.
// https://wpengine.com/support/determining-wp-engine-environment/
if ( !function_exists('is_wpe') || !is_wpe() ) {
  $plugins = array(
    'google-authenticator/google-authenticator.php'
  );
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');
  deactivate_plugins($plugins);
}

// Hiding the regular admin post, and comment pages
// To add pages to the list, add:
// remove_menu_page('edit.php?post_type=page');

add_action('admin_menu','remove_default_post_type');

function remove_default_post_type() {
  remove_menu_page('edit.php');
  remove_menu_page('edit-comments.php');
}

// Adds SVGs to media upload functionality
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

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

/**
 * For certain post types, we don't want to include preview/view links in the admin
 * panel because they do not have single pages that represent themselves. See:
 * http://wpsnipp.com/index.php/functions-php/hide-post-view-and-post-preview-admin-buttons/
 */
if ( is_admin() ) {
  // Defines the post types we're hiding "preview" links from:
  $removed_post_types = array(
    /* set post types */
    'homepage_tout',
    'homepage',
    'alert',
    'program_search_links'
  );

  // Remove the preview/view links in wp-admin for any post types that do not
  // have actual page URLs to visit.
  function remove_row_actions( $actions ) {
    global $removed_post_types;
    if( in_array(get_post_type(), $removed_post_types) )
      unset( $actions['view'] );
      unset( $actions['preview'] );
    return $actions;
  }

  // Hides various links in the admin list views
  add_filter( 'post_row_actions', 'remove_row_actions', 10, 1 );

  // Hides the preview button in the admin edit page
  function posttype_admin_css() {
    global $post_type;
    global $removed_post_types;
    if(in_array($post_type, $removed_post_types))
    echo '<style type="text/css">#post-preview, #view-post-btn{display: none;}</style>';
  }
  add_action( 'admin_head-post-new.php', 'posttype_admin_css' );
  add_action( 'admin_head-post.php', 'posttype_admin_css' );
}

/**
* Add additional query variables
*/
function access_add_query_vars( $vars ) {
  $vars[] = 'program_cat';
  $vars[] = 'pop_served';
  $vars[] = 'page_type';
  return $vars;
}
add_filter( 'query_vars', 'access_add_query_vars' );

/**
* Filter search to only show program page results
*/
function search_filter($query) {
  if ($query->is_search) {
    $query->set('post_type', 'programs');
  }
  return $query;
}

if (!is_admin()) {
  add_filter('pre_get_posts','search_filter');
}

/**
* Filter posts by multiple categories
*/
function access_filter_posts( $query ) {

  if ( is_post_type_archive( 'programs' ) && !is_admin() ) {
    $query->set( 'posts_per_page', 5 );
  }

  if ( ($query->is_home() || $query->is_search() || $query->is_archive()) && $query->is_main_query() && !is_admin() ) {

    $category_query = array();
    // Get the selected category, page type, or populations served, if any
    if ( !empty( $query->get( 'program_cat' ) ) ) {
      $category_query[] = array(
        'taxonomy' => 'programs',
        'field' => 'slug',
        'terms' => $query->get( 'program_cat' ),
      );
    }
    if ( !empty( $query->get( 'pop_served' ) ) ) {
      $category_query[] = array(
        'taxonomy' => 'populations-served',
        'field' => 'slug',
        'terms' => $query->get( 'pop_served' ),
      );
    }
    if ( !empty( $query->get( 'page_type' ) ) ) {
      $category_query[] = array(
        'taxonomy' => 'page-type',
        'field' => 'slug',
        'terms' => $query->get( 'page_type' ),
      );
    }
    if ( !empty( $category_query ) ) {
      $category_query['relation'] = 'AND';
      $query->set('tax_query', $category_query);
    }
  }
}
add_action( 'pre_get_posts', 'access_filter_posts' );

// Define site.
class BSDStarterSite extends TimberSite {
  function __construct() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'menus' );
    add_action( 'init', array( $this, 'cleanup_header' ) );
    add_action( 'init', array( $this, 'add_menus' ) );
    add_filter( 'timber_context', array( $this, 'add_to_context' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'add_styles_and_scripts' ), 999 );
    add_action( 'widgets_init', array( $this, 'add_sidebars' ) );
    parent::__construct();
  }

  function cleanup_header() {
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'index_rel_link' );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
  }

  function add_to_context ( $context ) {
    $context['menu'] = new TimberMenu('header-menu');
    $context['language_code'] = ICL_LANGUAGE_CODE;
    $context['site'] = $this;
    $context['search_links'] = Timber::get_posts(array(
      'post_type' => 'program_search_links',
      'numberposts' => 1
    ));
    $context['footer_widgets'] = Timber::get_widgets('footer_widgets');
    $context['footer_get_help_now_menu'] = new TimberMenu('get-help-now');
    $context['footer_for_caseworkers_menu'] = new TimberMenu('for-caseworkers');
    $context['footer_programs_menu'] = new TimberMenu('programs');
    $context['footer_about_access_nyc_menu'] = new TimberMenu('about-access-nyc');

    // Gets object containing all program categories
    $context['categories'] = get_terms( 'programs' );

    // Gets the page ID for top level nav items
    $context['programsLink'] = get_page_by_path( 'programs' );
    $context['eligibilityLink'] = get_page_by_path( 'eligibility' );
    $context['locationsLink'] = get_page_by_path( 'locations' );

    // Determines if page is in debug mode.
    $context['is_debug'] = isset($_GET['debug']) ? $_GET['debug'] : false;

    // Determine if page is in print view.
    $context['is_print'] = isset($_GET['print']) ? $_GET['print'] : false;

    return $context;
  }

  function add_styles_and_scripts() {
    global $wp_styles;
  }

  function add_sidebars() {
    register_sidebar(array(
      'id' => 'footer_widgets',
      'name' => __('Footer'),
      'description' => __('Widgets in the site global footer'),
      'before_widget' => '',
      'after_widget' => ''
    ));

    register_sidebar(array(
      'id' => 'sidebar',
      'name' => __('Default Sidebar'),
      'description' => __('Default sidebar for interior pages'),
      'before_widget' => '',
      'after_widget' => '',
      'before_title' => '<h3>',
      'after_title' => '</h3>'
    ));

    register_sidebar(array(
      'id' => 'sidebar_blog',
      'name' => __('Blog Sidebar'),
      'description' => __('Special sidebar for the blog'),
      'before_widget' => '',
      'after_widget' => '',
      'before_title' => '<h3>',
      'after_title' => '</h3>'
    ));
  }

  function add_menus() {
    register_nav_menus(
      array(
        'header-menu' => __( 'Header Menu' )
      )
    );
  }
}

new BSDStarterSite();

/**
* Returns the sidebar id for the page, based on page section
*/
function bsdstarter_get_sidebar_slug( $post ) {
  if ( $post->post_type == 'page' ) {
    $parents = array_reverse( get_post_ancestors( $post->ID ) );
    $slug = '_';
    // If there are no parents, the page itself is a top-level page
    if (empty($parents)) {
      $slug .= $post->post_name;
    } else {
      $ancestor = get_post($parents[0] );
      $slug .= $ancestor->post_name;
    }

    return $slug;
  }

  // For blog posts, get the blog sidebar
  if ( $post->post_type == 'post' ) {
    return 'blog';
  }

  return '';
}

// Customize TinyMCE settings
require_once(get_template_directory() . '/includes/bsdstarter_editor_styles.php');

// Custom Shortcodes
require_once(get_template_directory() . '/includes/bsdstarter_shortcodes.php');

// Includes jQuery
if (!is_admin()) add_action("wp_enqueue_scripts", "my_jquery_enqueue", 11);
function my_jquery_enqueue() {
   wp_deregister_script('jquery');
}

function validate_params($namespace, $subject) {
  $patterns = array(
    'programs'=> '/^[A-Z0-9,]*$/',
    'categories'=> '/^[a-z,-]*$/',
    'date'=> '/^[0-9]*$/',
    'guid'=> '/^[a-zA-Z0-9]{13,13}$/',
    'step'=> '/^[a-z,-]*$/'
  );
  preg_match($patterns[$namespace], $subject, $matches);
  return (isset($matches[0])) ? $matches[0] : ''; // fail silently
}

/**
 * Template for post password form
 * @return [string] the form markup as a string
 */
function password_form() {
  global $post;
  $label = 'pwbox-'.(empty($post->ID) ? rand() : $post->ID);
  $form = '<form action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" method="post">
    <div class="screener-question-container">
      <label class="type-h3 c-blue-dark ff-sans-serif d-block" for="' . $label . '">' . __("Please enter your password", 'accessnyc-screener') . ' </label>
      <input name="post_password" id="' . $label . '" type="password" maxlength="20" class="d-block w-100" />
      <div class="m-top">
        <input type="submit" name="Submit" class="btn btn-primary w-100 m-0" value="' . esc_attr__( "Submit" ) . '" />
      </div>
    </div>
  </form>';
  return $form;
}

add_filter('the_password_form', 'password_form');

/**
 * Hack for requiring authentication based on "post views"
 * @param  [string] $role the path of the page to check authentication against
 * @return [boolean]      truthy if authenticated
 */
function requires_auth($role) {
  if (post_password_required(get_page_by_path($role)->ID)) {
    wp_redirect('/peu/login');
    exit;
  } else {
    return true;
  }
}

/**
 * Add expiry filter to post password tokens for one day
 */
// apply_filters('post_password_expires', 0);

Routes::map('locations', function() {
  Routes::load('locations.php', null, null, 200);
});

Routes::map('locations/json', function() {
  Routes::load('archive-location.php', null, null, 200);
});

Routes::map('eligibility', function() {
  Routes::load('screener.php', null, null, 200);
});

Routes::map('eligibility/results', function() {
  $params = array();
  $params['link'] = home_url().'/eligibility/results/';
  Routes::load('eligibility-results.php', $params, null, 200);
});

Routes::map('peu', function() {
  requires_auth('peu');
  Routes::load('screener-field.php', null, null, 200);
});

Routes::map('peu/results', function() {
  requires_auth('peu');
  $params = array();
  $params['link'] = home_url().'/peu/results/';
  Routes::load('eligibility-results-field.php', $params, null, 200);
});

Routes::map('peu/login', function() {
  if (!post_password_required(get_page_by_path('peu')->ID)) {
    wp_redirect('/peu');
    exit;
  }
  Routes::load('screener-login.php', null, null, 200);
});
