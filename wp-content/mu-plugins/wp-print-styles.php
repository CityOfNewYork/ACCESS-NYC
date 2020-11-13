<?php

// phpcs:disable
/**
 * Plugin Name: WP Print Styles (Disable Styles)
 * Description: Disable the WP Security Questions stylesheet and the WordPress Block Library stylesheet (if a post doesn't have blocks).
 * Author: NYC Opportunity
 */
// phpcs:enable

add_action('wp_print_styles', function() {
  if (false === is_admin() && false === is_user_logged_in()) {
    wp_deregister_style('wsq-frontend.css');

    /** Only deregister blocks script if the post doesn't have blocks */
    if (false === has_blocks()) {
      wp_deregister_style('wp-block-library');
    }
  }
});
