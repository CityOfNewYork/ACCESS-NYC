<?php

/**
 * Template name: Field Screener
 */

$dir = WPMU_PLUGIN_DIR . '/anyc-field-screener';

if (file_exists($dir)) {
  require_once $dir . '/Views.php';

  new FieldScreener\Views();

  $post = Timber::get_post();

  echo do_shortcode($post->post_content);
}
