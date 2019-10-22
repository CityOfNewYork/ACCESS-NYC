<?php

/**
 * Plugin Name: Pre Get Posts - Search
 * Description: Intercepts the main WP Query before it is made. Filter search to only show program page results.
 * Author: NYC Opportunity
 */

add_filter('pre_get_posts', function($query) {
  if (is_admin()) {
    return $query;
  }

  if ($query->is_search) {
    $query->set('post_type', 'programs');
  }

  /** Always return the query */
  return $query;
});
