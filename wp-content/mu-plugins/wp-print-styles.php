<?php

/**
 * Plugin Name: WP Print Styles (Disable Styles)
 * Description: Disable the WP Security Questions stylesheet.
 * Author: NYC Opportunity
 */

add_action('wp_print_styles', function() {
  if (false === is_admin() && false === is_user_logged_in()) {
    wp_deregister_style('wsq-frontend.css');
  }
}, 100);
