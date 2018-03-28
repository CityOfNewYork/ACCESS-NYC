<?php

/**
 * Script
 */

/**
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 * @param  [string] $name the name of the script source
 * @return null
 */
function enqueue_script($name) {
  require_once ABSPATH . '/vendor/nyco/wp-assets/dist/script.php';

  $script = Nyco\Enqueue\script($name, '.min');
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
