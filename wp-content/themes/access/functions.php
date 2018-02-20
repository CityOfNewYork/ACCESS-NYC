<?php

// Notifications
require_once(get_template_directory() . '/includes/notifications.php');

Notifications\timber();

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

// *****
// modifying program url in the language switcher
function wpml_switcher_urls($languages) {
  global $sitepress;

  if(isset($_GET['program_cat'])){

    $cur_prog=$_GET['program_cat'];
    $original_lang = ICL_LANGUAGE_CODE; // Save the current language

    // switch to english to capture the original taxonomies
    if($original_lang != 'en'){
      $sitepress->switch_lang('en');
    }

    // retrieve the program taxonomies as array
    $terms = get_terms( array(
      'taxonomy' => 'programs',
      'hide_empty' => false,  ) );

    $sitepress->switch_lang($original_lang); //switch back to the original language

    // find the en taxonomy that matches the current program
    foreach ($terms as $term) {
      if (strpos($cur_prog, $term->slug) !== false) {
        $prog = $term->slug;
      }
    }

    // reconstruct the language url based on the program filter
    if(strpos(basename($_SERVER['REQUEST_URI']), 'program_cat') !== false){
      foreach($languages as $lang_code => $language){
        if($lang_code == 'en'){
          $newlang_code="";
          $languages[$lang_code]['url'] = '/programs/?program_cat='.$prog;
        }
        // if not english, then remove the language code and add the correct one
        elseif($lang_code != 'en' || $lang_code != '' ){
          $languages[$lang_code]['url'] = '/'.$lang_code.'/programs/?program_cat='.$prog.'-'.$lang_code;
        }
      }
    }
  }
  return $languages;
}
add_filter('icl_ls_languages', 'wpml_switcher_urls');
// end modifying url
// *****

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
    error_reporting(0);
    $context['language_code'] = ICL_LANGUAGE_CODE;
    error_reporting(WP_DEBUG);
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
if (!is_admin()) add_action('wp_enqueue_scripts', 'my_jquery_enqueue', 11);
function my_jquery_enqueue() {
   wp_deregister_script('jquery');
}

// *****
// Function to trigger Google Maps render on content import
global $office_loc;
$office_loc = array(
  'post_types' => array('location'),
  'fields' => array(
    'google_map' => 'field_588003b6be759',
    'address_street'   => 'field_58800318be754',
    'address_street_2' => 'field_5880032abe755',
    'city'      => 'field_58acf5f524f67',
    'zip'       => 'field_58acf60c24f68',
  ),
);

function trigger_gmaps(){
  global $post;
  global $office_loc;

  if ( $post && $post->post_type == 'location' ){
    // get the address field for the google map
    $location = get_field( $office_loc['fields']['google_map'], $post->ID );

    // check to see if the address is empty - if empty, populated it with the correct fields
    if ( isset($location['address']) && isset($location['lat']) && isset($location['lng'])  ) {
      $full_address =  $location['address'];
    }else{
      // create a location array to be populated
      $location = array(
        'address' => '',
        'lat' => '',
        'lng' => ''
      );

      // create a full address
      $full_address = get_field( $office_loc['fields']['address_street'], $post->ID ) . ', ' .get_field( $office_loc['fields']['city'], $post->ID ) . ' ' . get_field( $office_loc['fields']['zip'], $post->ID );

      $address = urlencode($full_address); // Spaces as + signs

      // will want to replace the key
      $address_query = wp_remote_get("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=AIzaSyBEl7iNZDAToQVavHuJW4D_PKPmoVpU7H4");
      $address_json = wp_remote_retrieve_body( $address_query );
      $address_data = json_decode($address_json);
      // echo '<div class="error"><p>http://maps.google.com/maps/api/geocode/json?address=' . $address .'&sensor=false&key=AIzaSyBEl7iNZDAToQVavHuJW4D_PKPmoVpU7H4</p></div>';

      // if the address contains info
      if(isset($address_data)){
        echo var_dump($address_data);
        $lat = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $lng = $address_data->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
      }
      // set the new address
      $location['address'] = $full_address;
      $location['lat'] = $lat;
      $location['lng'] = $lng;


      // update the address, latitude, and longitude field
      update_post_meta( $post->ID, 'address', $location['address'] );
      update_post_meta( $post->ID, 'lat', $location['lat'] );
      update_post_meta( $post->ID, 'lng', $location['lng'] );

      // update the google map fields
      update_field( $office_loc['fields']['google_map'], $location, $post->ID);
    }
  }
}
add_action('wp', 'trigger_gmaps', 1);
// end of trigger_gmaps
// *****

// GatherContent - Mapped WordPress Field meta_keys edit
add_filter( 'gathercontent_importer_custom_field_keys', function( $meta_keys ) {
  // empty array that will contain the unique meta keys for the mapped fields
  $new_meta_keys = array();

  // Creates a new array of meta_keys that are not prefixed with underscore
  foreach ($meta_keys as $key=>$value) {
    if (substr($value, 0, 1) != '_') {
      $new_meta_keys[$key]=$value;
    }
  }
  // return the new array
  return $new_meta_keys;
} );
// end of GatherContent - Mapped WordPress Field meta_keys edit

// add meta description to head
function access_meta_description($title)
{
  // render only on the homepage
  if( is_home()){
    echo '<meta name="description" content="' . get_bloginfo('description') . '" />' . "\r\n";
    // remove tagline from title tag
    $title = get_bloginfo('name');
  }
  return $title;
}
add_action( 'pre_get_document_title', 'access_meta_description', 10, 1);
// end add meta description to head

/**
 * Validate params through regex
 * @param  string $namespace - the namespace of the parameter
 * @param  string $subject   - the string to validate
 * @return string            - returns blank string if false, parameter if valid
 */
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
 * Creates a shareable url along with valid hash
 * @param  array $params - requires programs, categories, date, guid, share_link
 * @return array         - 0; the url 1; the hash
 */
function share_data($params) {
  $query = array();
  $data = array();

  // Gets the URL Parameters for the search value,
  if (isset($params['programs'])) {
    $query['programs'] = validate_params(
      'programs', urldecode(htmlspecialchars($params['programs']))
    );
  }

  if (isset($params['categories'])) {
    $query['categories'] = validate_params(
      'categories', urldecode(htmlspecialchars($params['categories']))
    );
  }

  if (isset($params['date'])) {
    $query['date'] = validate_params(
      'date', urldecode(htmlspecialchars($params['date']))
    );
  }

  if (isset($params['guid'])) {
    $query['guid'] = validate_params(
      'guid', urldecode(htmlspecialchars($params['guid']))
    );
  }

  // Build query
  $http_query = http_build_query($query);
  $http_query = (isset($http_query)) ? '?'.$http_query : '';
  $url = home_url().$params['path'].$http_query;
  $hash = \SMNYC\hash($url);

  return array('url' => $url, 'hash' => $hash, 'query' => $query);
}

/**
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 * @param  [string] $name the name of the script source
 * @return null
 */
require_once(
  get_template_directory() .
  '/vendor/nyco/wp-assets/dist/script.php'
);

/**
 * Enqueue a hashed style based on it's name and language prefix.
 * @param  [string] $name the name of the stylesheet source
 * @return null
 */
function enqueue_language_style($name) {
  require_once(
    get_template_directory() .
    '/vendor/nyco/wp-assets/dist/style.php'
  );

  error_reporting(0);
  $lang = (ICL_LANGUAGE_CODE === 'en') ? 'default' : ICL_LANGUAGE_CODE;
  error_reporting(WP_DEBUG);

  Nyco\Enqueue\style("$name-$lang");
}

/**
 * Routing Configuration
 */
require_once(get_template_directory() . '/includes/routing.php');
