<?php

/**
 * Plugin Name: WP Print Styles (Disable Styles)
 * Description: Disable the WP Security Questions stylesheet.
 * Author: NYC Opportunity
 */

add_action('wp_print_styles', function () {
  wp_deregister_style('wsq-frontend.css');
}, 100);
