<?php

// phpcs:disable
/**
 * Plugin Name: Configure Page Support
 * Description: Removes support for "author", "comments", and "discussion" from the "Page" post type. Disables the Gutenberg Editor for the legacy template.
 * Author: NYC Opportunity
 */
// phpcs:enable

add_action('init', function() {
  remove_post_type_support('page', 'author');
  remove_post_type_support('page', 'comments');
  remove_post_type_support('page', 'discussion');

  // Disable the Gutenberg Editor for the legacy template
  add_filter('use_block_editor_for_post_type', function($useBlockEditor, $postType) {
    if ('page' === $postType) {
      return !strpos(get_page_template(), 'single-page-legacy');
    } else {
      return $useBlockEditor;
    }
  }, 10, 2);
});
