<?php

class Site extends TimberSite {

  function __construct() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('menus');

    add_action('init', array($this, 'cleanUpHeader'));
    add_action('init', array($this, 'addMenus'));

    add_filter('timber_context', array($this, 'addToContext'));
    add_action('wp_enqueue_scripts', array($this, 'addStylesAndScripts'), 999);
    // add_action('widgets_init', array( $this, 'add_sidebars' ));
    parent::__construct();
  }

  function cleanUpHeader() {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
  }

  function addToContext($context) {
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
    $context['peu_header_menu'] = new TimberMenu('peu');

    // Gets object containing all program categories
    $context['categories'] = get_terms('programs');
    // Display alert
    $context['page_alert'] = Timber::get_post(array(
      'post_type' => 'alert'
    ));
    // Gets the page ID for top level nav items
    $context['programsLink'] = get_page_by_path('programs');
    $context['eligibilityLink'] = get_page_by_path('eligibility');
    $context['locationsLink'] = get_page_by_path('locations');

    // Determines if page is in debug mode.
    $context['is_debug'] = isset($_GET['debug']) ? $_GET['debug'] : false;

    // Determine if page is in print view.
    $context['is_print'] = isset($_GET['print']) ? $_GET['print'] : false;

    // Get the META description - return english if empty
    if (ICL_LANGUAGE_CODE != 'en') {
      $orig_page_id = get_page_by_path(trim($_SERVER["REQUEST_URI"], '/' . ICL_LANGUAGE_CODE))->ID;
    } else {
      $orig_page_id = get_page_by_path(trim($_SERVER["REQUEST_URI"], '/'))->ID;
    }

    $page_id = apply_filters('wpml_object_id', $orig_page_id, 'page', true, ICL_LANGUAGE_CODE);

    $page_desc = get_field('page_meta_description', $page_id);

    if ($page_desc == '') {
      $page_desc = get_field('page_meta_description', $orig_page_id);
    }

    if (is_home()) {
      $context['page_meta_desc'] = get_bloginfo('description');
    } elseif ($page_id || $page_desc) {
      $context['page_meta_desc'] = $page_desc;
    }
    // end Get the META description

    return $context;
  }

  function addStylesAndScripts() {
    global $wp_styles;
  }

  // function add_sidebars() {
  //   register_sidebar(array(
  //     'id' => 'footer_widgets',
  //     'name' => __('Footer'),
  //     'description' => __('Widgets in the site global footer'),
  //     'before_widget' => '',
  //     'after_widget' => ''
  //   ));
  // }

  function addMenus() {
    register_nav_menus(
      array(
        'header-menu' => __('Header Menu')
      )
    );
  }
}
