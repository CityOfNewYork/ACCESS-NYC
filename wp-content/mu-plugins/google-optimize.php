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
      'page_title'  => __('Google Optimize Settings'),
      'menu_title'  => __('Google Optimize Settings'),
      'redirect'    => false,
    ));
  }
}
