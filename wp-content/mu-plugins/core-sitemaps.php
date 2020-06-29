<?php

// phpcs:disable
/**
 * Plugin Name: Configure Core Sitemaps
 * Description: Configuration for the proposed WordPress core plugin for simple sitemaps. Filters out users, taxonomies, and other post types that do not have page views.
 * Author: NYC Opportunity
 */
// phpcs:enable

/**
 * Filters whether XML Sitemaps are enabled or not. This ensures that they are
 * even when the WordPress "Site Public" option is set to false.
 */
add_filter('wp_sitemaps_is_enabled', '__return_true');

/**
 * Filters the list of registered sitemap providers.
 *
 * @param  Array  $providers  Array of Core_Sitemap_Provider objects.
 */
add_filter('wp_sitemaps_register_providers', function($providers) {
  unset($providers['taxonomies']);
  unset($providers['users']);

  return $providers;
});

/**
 * Filter the list of post object sub types available within the sitemap.
 *
 * @param  Array  $post_types  List of registered object sub types.
 */
add_filter('wp_sitemaps_post_types', function($post_types) {
  unset($post_types['post']);
  unset($post_types['homepage']);
  unset($post_types['homepage_tout']);
  unset($post_types['smnyc-sms']);
  unset($post_types['smnyc-email']);
  unset($post_types['program_search_links']);
  unset($post_types['alert']);

  return $post_types;
});
