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
  $script = Nyco\Enqueue\script($name, '.min');
  if ($cors) add_crossorigin_attr($name);
}

/**
 * Helper to add cross origin anonymous attribute to scripts.
 * @param [string] $name The name of the script.
 */
function add_crossorigin_attr($name) {
  $name = end(explode('/', $name));
  add_filter('script_loader_tag', function($tag, $handle) use ($name) {
    if ($name === $handle)
      return str_replace(' src', ' crossorigin="anonymous" src', $tag);
  }, 10, 2);
}

/**
 * Disable the oEmbed script
 * @return null
 */
function deregister_wp_embed() {
  wp_deregister_script('wp-embed');
} add_action('wp_print_scripts', 'deregister_wp_embed', 100);

/**
 * Disable the WP Security Questions script
 * @return null
 */
function deregister_wp_security_questions() {
  wp_deregister_script('wsq-frontend.js');
} add_action('wp_print_scripts', 'deregister_wp_security_questions', 100);

