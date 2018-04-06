<?php

/**
 * WP Engine Production environment config
 */

// Discourage search engines
// @url https://codex.wordpress.org/Option_Reference#Privacy
if (null !== WP_BLOG_PUBLIC) {
  update_option('blog_public', WP_BLOG_PUBLIC);
}