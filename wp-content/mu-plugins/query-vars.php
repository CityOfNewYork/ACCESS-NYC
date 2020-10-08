<?php

// phpcs:disable
/**
 * Plugin Name: Query Vars
 * Description: Adds 'program_cat', 'pop_served', and 'page_type' as acceptable query vars to the site for WordPress Database queries.
 * Author: NYC Opportunity
 */
// phpcs:enable

add_filter('query_vars', function($vars) {
  $vars[] = 'program_cat'; // Used in Programs Archive
  $vars[] = 'pop_served'; // Used in Programs Single
  $vars[] = 'page_type'; // Used in Programs Single
  $vars[] = 'print';

  /**
   * Please note, custom query parameters should use the prefix
   * to prevent further conflicts with the WordPress Query
   *
   * @author NYC Opportunity
   */

  $prefix = 'anyc_';

  $vars[] = $prefix . 'categories'; // Program Categories
  $vars[] = $prefix . 'served'; // Population Served
  $vars[] = $prefix . 'print'; // Print Views
  $vars[] = $prefix . 'v'; // A/B Testing Variants

  return $vars;
});
