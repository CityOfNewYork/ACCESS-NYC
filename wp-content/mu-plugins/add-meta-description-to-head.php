<?php

/**
 * Plugin Name: Add Meta Description to Head
 * Description: Adds the description defined in the WordPress Admin settings to the description meta tag in the head for the homepage only.
 * Author: Blue State Digital
 */

add_action('pre_get_document_title', function ($title) {
  // render only on the homepage
  if (is_home()) {
    echo '<meta name="description" content="' . get_bloginfo('description') . '" />' . "\r\n";
    // remove tagline from title tag
    $title = get_bloginfo('name');
  }

  return $title;
}, 10, 1);