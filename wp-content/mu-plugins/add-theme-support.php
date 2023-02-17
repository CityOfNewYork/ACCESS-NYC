<?php

/**
 * Plugin Name: Add Theme Support
 * Description: Add theme support items. Currently, title tag, menus and editor styles are added.
 * Author: Blue State Digital
 */

add_action('after_setup_theme', function() {
  add_theme_support('title-tag');
  add_theme_support('menus');
  add_theme_support('dark-editor-style');
});
