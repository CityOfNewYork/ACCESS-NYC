<?php

/**
 * Plugin Name: Query Vars
 * Description: Adds 'program_cat', 'pop_served', and 'page_type' as acceptable query vars to the site for WordPress Database queries.
 * Author: NYC Opportunity
 */

add_filter('query_vars', function($vars) {
  $vars[] = 'program_cat'; // Used in Programs Archive
  $vars[] = 'pop_served'; // Used in Programs Single
  $vars[] = 'page_type'; // Used in Programs Single

  return $vars;
});