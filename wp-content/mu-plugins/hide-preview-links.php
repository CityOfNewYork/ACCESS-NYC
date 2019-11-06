<?php

// phpcs:disable
/**
 * Plugin Name: Hide Preview
 * Description: For certain post types, we don't want to include preview/view links in the admin panel because they do not have single pages that represent themselves.
 * Author: Blue State Digital
 * @source: http://wpsnipp.com/index.php/functions-php/hide-post-view-and-post-preview-admin-buttons/
 */
// phpcs:enable

require plugin_dir_path(__FILE__) . '/hide-preview-links/HidePreviewLinks.php';

$removed_post_types = array(
  'homepage_tout',
  'homepage',
  'alert',
  'program_search_links'
);

add_filter('post_row_actions', 'HidePreviewLinks\post_type_links', 10, 1);
add_action('admin_head-post-new.php', 'HidePreviewLinks\post_type_admin_css');
add_action('admin_head-post.php', 'HidePreviewLinks\post_type_admin_css');
