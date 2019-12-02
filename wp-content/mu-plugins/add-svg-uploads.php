<?php

/**
 * Plugin Name: Add SVG Upload Support
 * Description: Adds SVGs mime type to Media uploader to enable support for SVG files.
 * Author: Blue State Digital
 */

add_filter('upload_mimes', function($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
});
