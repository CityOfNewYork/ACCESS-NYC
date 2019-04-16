<?php

/**
 * Script
 */

/**
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 * @param  [string]  $name The name of the script source.
 * @param  [boolean] $cors Add the crossorigin="anonymous" attribute.
 * @return null
 */
function enqueue_script($name, $cors = false) {
  require_once ABSPATH . '/vendor/nyco/wp-assets/dist/script.php';
  $script = Nyco\Enqueue\script($name);

  if ($cors) {
    add_crossorigin_attr($name);
  }
}

/**
 * Helper to add cross origin anonymous attribute to scripts.
 * @param [string] $name The name of the script.
 */
function add_crossorigin_attr($name) {
  $name = end(explode('/', $name));
  add_filter('script_loader_tag', function ($tag, $handle) use ($name) {
    if ($name === $handle) {
      return str_replace(' src', ' crossorigin="anonymous" src', $tag);
    }
  }, 10, 2);
}

/**
 * Disable Scripts
 * @return null
 */
add_action('wp_print_scripts', function () {
  /** Disable the oEmbed script */
  wp_deregister_script('wp-embed');
  /** Disable the WP Security Questions script */
  wp_deregister_script('wsq-frontend.js');
  /** Disable jQuery */
  wp_deregister_script('jquery');
}, 100);
