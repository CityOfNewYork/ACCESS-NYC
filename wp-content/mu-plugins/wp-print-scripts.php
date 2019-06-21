<?php

/**
 * Plugin Name: WP Print Scripts (Disable Scripts)
 * Description: Disable the oEmbed, WP Security Questions, and jQuery scripts.
 * Author: NYC Opportunity
 */

add_action('wp_print_scripts', function () {
  /** Disable the oEmbed script */
  wp_deregister_script('wp-embed');

  /** Disable the WP Security Questions script */
  wp_deregister_script('wsq-frontend.js');

  /** Disable jQuery */
  if (false === is_admin() && false === is_user_logged_in()) {
    wp_deregister_script('jquery');
  }
}, 100);
