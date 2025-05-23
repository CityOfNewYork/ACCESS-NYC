<?php

// phpcs:disable
/**
 * Plugin Name: Configure Core Sitemaps
 * Description: Configuration for the proposed WordPress core plugin for simple sitemaps. Filters out users, taxonomies, and other post types that do not have page views.
 * Author: NYC Opportunity
 */
// phpcs:enable

/**
 * Filters the list of registered sitemap providers.
 *
 * @param  Array  $providers  Array of Core_Sitemap_Provider objects.
 */
add_filter('wp_sitemaps_add_provider', function($provider, $name) {
  if ($name === 'users') {
    return false;
  }

  if ($name === 'taxonomies') {
    return false;
  }

  return $provider;
}, 10, 2);

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
  unset($post_types['search_suggestions']);
  unset($post_types['alert']);

  return $post_types;
});

// Remove eligibility results from sitemap
add_filter('wp_sitemaps_posts_query_args', function($args, $post_type) {
  if ($post_type === 'page') {
      // Get the ID of the page you want to exclude
      $page_to_exclude = get_page_by_path('eligibility/results');
    if ($page_to_exclude) {
        $args['post__not_in'][] = $page_to_exclude->ID;
    }
  }
  return $args;
}, 10, 2);
