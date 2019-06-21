<?php

/**
 * Plugin Name: Hide Preview/View Links
 * Description: For certain post types, we don't want to include preview/view links in the admin panel because they do not have single pages that represent themselves.
 * Author: Blue State Digital
 * @source: http://wpsnipp.com/index.php/functions-php/hide-post-view-and-post-preview-admin-buttons/
 */

// Defines the post types we're hiding "preview" links from:
$removed_post_types = array(
  /* set post types */
  'homepage_tout',
  'homepage',
  'alert',
  'program_search_links'
);

/**
 * Remove the preview/view links in wp-admin for any post types that do not
 * have actual page URLs to visit.
 */
add_filter('post_row_actions', function($actions) {
  global $removed_post_types;
  if (is_admin() && in_array(get_post_type(), $removed_post_types)) {
    unset($actions['view']);
    unset($actions['preview']);
  }
  return $actions;
}, 10, 1 );

/**
 * Hides the preview button in the admin edit page.
 */
function posttype_admin_css() {
  global $post_type;
  global $removed_post_types;
  if (is_admin() && in_array($post_type, $removed_post_types)) {
    echo '<style type="text/css">';
    echo '  #post-preview,#view-post-btn{display: none;}';
    echo '</style>';
  }
}

add_action('admin_head-post-new.php', 'posttype_admin_css');
add_action('admin_head-post.php', 'posttype_admin_css');
