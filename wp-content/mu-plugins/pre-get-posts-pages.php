<?php

// phpcs:disable
/**
 * Plugin Name: Pre Get Posts - Pages
 * Description: Intercepts the main WP Query before it is made. ensure pages will display if a custom post type query parameter appears in a url. The front-end has been using 'programs' to modify search results for Screening Results and Locations programs. This insures compatibility with those url query parameters.
 * Author: NYC Opportunity
 */
// phpcs:enable

add_filter('pre_get_posts', function($query) {
  if (is_admin()) {
    return $query;
  }

  include_once ABSPATH . 'wp-admin/includes/plugin.php';

  // Find out if the page name is set (path)
  $ispage = (null !== $query->query['pagename']) ? true : false;

  // If it is a page but the post type is not set to page, modify it to be so.
  if ($ispage && 'page' !== $query->query_vars['post_type']) {
    $page = get_page_by_path($query->query['pagename']);

    // We will need to get the translated ID of the parent page from WPML
    $wpml = 'sitepress-multilingual-cms/sitepress.php';
    $postparent = (is_plugin_active($wpml)) ? apply_filters(
      'wpml_object_id',
      $page->post_parent, $page->post_type, true, ICL_LANGUAGE_CODE
    ) :
      $page->post_parent;

    // Reset the query params for the desired query
    $query->set('name', $page->post_name);
    $query->set('post_parent', $postparent);
    $query->set('post_type', $page->post_type);
  }

  /** Always return the query */
  return $query;
});
