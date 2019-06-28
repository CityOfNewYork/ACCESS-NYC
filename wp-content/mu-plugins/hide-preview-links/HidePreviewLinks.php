<?php

namespace HidePreviewLinks;

/**
 * Remove the preview/view links in wp-admin for any post types that do not
 * have actual page URLs to visit.
 * @param   [type]  $actions  [$actions description]
 * @return  [type]            [return description]
 */
function post_type_links($actions) {
  global $removed_post_types;

  if (is_admin() && in_array(get_post_type(), $removed_post_types)) {
    unset($actions['view']);
    unset($actions['preview']);
  }

  return $actions;
}

/**
 * Hides the preview button in the admin edit page.
 */
function post_type_admin_css() {
  global $post_type;
  global $removed_post_types;

  if (is_admin() && in_array($post_type, $removed_post_types)) {
    echo '<style type="text/css">';
    echo '  #post-preview, #view-post-btn{ display: none; }';
    echo '</style>';
  }
}
