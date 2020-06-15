<?php

/**
 * Plugin Name: Add Meta Description to Head
 * Description: Removes the site tagline from the homepage title tag.
 * Author: Blue State Digital
 */

add_action('pre_get_document_title', function($title) {
  if (is_home()) {
    $title = get_bloginfo('name');
  }

  return $title;
}, 10, 1);
