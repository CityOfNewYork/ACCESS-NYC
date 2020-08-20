<?php

// phpcs:disable
/**
 * Plugin Name: Robots.txt
 * Description: Modifies the default output of WordPress' robots.txt based on the Search Engine Visibility Settings (Settings > Reading).
 * Plugin URI: https://github.com/cityofnewyork/access-nyc
 * Author: NYC Opportunity
 * Author URI: nyc.gov/opportunity
 */
// phpcs:enable

/**
 * Preview Robots.txt
 * @link /index.php?robots=1 or @link /robots.txt
 *
 * Looking for sitemap configuration?
 * @link https://github.com/GoogleChromeLabs/wp-sitemaps
 */
add_filter('robots_txt', function($output, $public) {
  /**
   * Wipe out the default robots.txt;
   * ---
   * User-agent: *
   * Disallow: /wp-admin/
   * Allow: /wp-admin/admin-ajax.php
   * ---
   */

  $output = '';
  $output .= 'User-agent: *';

  /**
   * If the site isn't public disallow everything
   * else add our own granular control
   */

  if (!$public) {
    $output .= "\nDisallow: /";
  } else {
    $output .= "\nDisallow: /wp-admin/";
    $output .= "\nDisallow: /wp-includes/";
    $output .= "\nDisallow: /wp-content/plugins/";
    $output .= "\nDisallow: /wp-content/mu-plugins/";
    $output .= "\nDisallow: /readme.html";
    $output .= "\nDisallow: /README.md";
    $output .= "\nAllow: /wp-admin/admin-ajax.php";
    $output .= "\nAllow: /wp-content/uploads/";
    $output .= "\nAllow: /wp-includes/css/dist/block-library/";
    $output .= "\n";
  }

  return $output;
}, 0, 2);
