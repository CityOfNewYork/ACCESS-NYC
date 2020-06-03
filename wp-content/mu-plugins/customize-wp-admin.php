<?php

// phpcs:disable
/**
 * Plugin Name: Customize WordPress Admin
 * Description: Customize the display of the WordPress Admin. Remove menu items and make the wp admin bar statically positioned when viewing the theme.
 * Author: NYC Opportunity
 */
// phpcs:enable

/**
 * Remove menu items
 */

add_action('wp_before_admin_bar_render', function() {
  global $wp_admin_bar;

  $wp_admin_bar->remove_node('wp-logo');
  $wp_admin_bar->remove_node('search');
  $wp_admin_bar->remove_node('customize');
  $wp_admin_bar->remove_node('comments');
  $wp_admin_bar->remove_node('updates');
  $wp_admin_bar->remove_node('new_draft');
}, 99);

/**
 * Add our own styling for the WP Admin Bar.
 * This will only work properly for versions >= 5.4 where WP inserts the admim
 * bar using the wp_body_open filter (if present in the theme).
 */

if (version_compare(get_bloginfo('version'), '5.4', '>=')) {
  add_action('admin_bar_init', function() {
    // Remove the admin bar HTML styling
    remove_action('wp_head', '_admin_bar_bump_cb');

    // Add our theme's admin bar styling
    if (false === is_admin()) {
      wp_register_style(
        'wp-admin-bar',
        get_stylesheet_directory_uri() . '/wp-admin-bar.css'
      );
      wp_enqueue_style('wp-admin-bar');
    }
  });
}
