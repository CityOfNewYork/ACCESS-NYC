<?php
/**
 * Register custom query vars
 *
 * @param array $vars The array of available query variables
 *
 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/query_vars
 */
add_filter('query_vars', function($vars) {
  $vars[] = 'program_cat'; // Used in Programs Archive
  $vars[] = 'pop_served'; // Used in Programs Single
  $vars[] = 'page_type'; // Used in Programs Single

  return $vars;
});