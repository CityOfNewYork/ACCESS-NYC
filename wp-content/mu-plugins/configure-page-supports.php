<?php

/**
 * Plugin Name: Configure Page Support
 * Description: Removes support for "author", "editor", "comments", and "discussion" from the "Page" post type. Fix for WordPress 5 (Gutenberg). Leaving the "editor" enabled rendered the editing page blank. However, the content in "Pages" was defined by Advanced Custom Fields so the "editor" was not needed.
 * Author: NYC Opportunity
 */

add_action('init', function() {
  remove_post_type_support('page', 'author');
  remove_post_type_support('page', 'editor');
  remove_post_type_support('page', 'comments');
  remove_post_type_support('page', 'discussion');
});