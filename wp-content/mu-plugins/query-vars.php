<?php
/**
 * Register custom query vars
 *
 * @param array $vars The array of available query variables
 *
 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/query_vars
 */
add_filter('query_vars', function($vars) {
  $vars[] = 'program_cat';
  $vars[] = 'pop_served';
  $vars[] = 'page_type';

  return $vars;
});