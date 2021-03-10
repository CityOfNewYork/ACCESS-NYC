<?php

/**
 * Create Google Optimize Settings Page
 */
add_action('acf/init', 'my_acf_op_init');

function my_acf_op_init() {
  // Check function exists.
  if (function_exists('acf_add_options_page')) {
    // Add parent.
    $parent = acf_add_options_page(array(
      'page_title'  => __('Data Layer'),
      'menu_title'  => __('Integrations'),
      'redirect'    => false,
    ));

    // Add sub page.
    $google_optimize = acf_add_options_sub_page(array(
      'page_title'  => __('Google Optimize'),
      'menu_title'  => __('Google Optimize'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $rollbar = acf_add_options_sub_page(array(
      'page_title'  => __('Rollbar'),
      'menu_title'  => __('Rollbar'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $webtrends = acf_add_options_sub_page(array(
      'page_title'  => __('Webtrends'),
      'menu_title'  => __('Webtrends'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $google_analytics = acf_add_options_sub_page(array(
      'page_title'  => __('Google Analytics'),
      'menu_title'  => __('Google Analytics'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $google_tag_manager = acf_add_options_sub_page(array(
      'page_title'  => __('Google Tag Manager'),
      'menu_title'  => __('Google Tag Manager'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $google_recaptcha = acf_add_options_sub_page(array(
      'page_title'  => __('Google Recaptcha'),
      'menu_title'  => __('Google Recaptcha'),
      'parent_slug' => $parent['menu_slug'],
    ));

    $google_translate_element = acf_add_options_sub_page(array(
      'page_title'  => __('Google Translate'),
      'menu_title'  => __('Google Translate'),
      'parent_slug' => $parent['menu_slug'],
    ));

  }
}
