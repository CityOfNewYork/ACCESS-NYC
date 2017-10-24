<?php
/**
 * Screener Login
 * Most of the magic here happens in JavaScript. The only thing we want is a list
 * of program categories.
 */

if ( ! class_exists( 'Timber' ) ) {
  echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  return;
}

$context = Timber::get_context();

$templates = array('screener-login.twig');

Timber::render($templates, $context);
