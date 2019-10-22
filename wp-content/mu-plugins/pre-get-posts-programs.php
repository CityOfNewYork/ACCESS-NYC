<?php

// phpcs:disable
/**
 * Plugin Name: Pre Get Posts - Programs
 * Description: Intercepts the main WP Query before it is made. Intercepts the custom query vars and builds the query based on them for the /programs archive and search archive. This is the first step to filtering programs. The front-end script programs.js intercepts these queries and maps them to WP REST Api filters.
 * Author: NYC Opportunity
 */
// phpcs:enable

add_filter('pre_get_posts', function($query) {
  if (is_admin()) {
    return $query;
  }

  /**
   * Set the pagination amount of viewable posts to 5
   */
  if (is_post_type_archive('programs')) {
    $query->set('posts_per_page', 5);
  }

  if ($query->is_search() || $query->is_archive()) {
    $category_query = array();

    /**
     * Get the selected category, page type, or populations served, if any.
     * Links to filtering programs are archive used in Single programs view and
     * in the footer menu. The programs script (src/programs.js) will map
     * these filters to filters used by the WP REST Api for retrieving programs.
     */
    if (!empty($query->get('program_cat'))) {
      $category_query[] = array(
        'taxonomy' => 'programs',
        'field' => 'slug',
        'terms' => $query->get('program_cat'),
      );
    }

    if (!empty($query->get('pop_served'))) {
      $category_query[] = array(
        'taxonomy' => 'populations-served',
        'field' => 'slug',
        'terms' => $query->get('pop_served'),
      );
    }

    // This category is not in use currently.
    if (!empty($query->get('page_type'))) {
      $category_query[] = array(
        'taxonomy' => 'page-type',
        'field' => 'slug',
        'terms' => $query->get('page_type'),
      );
    }

    if (!empty($category_query)) {
      $category_query['relation'] = 'AND';
      $query->set('tax_query', $category_query);
    }
  }

  /** Always return the query */
  return $query;
});
