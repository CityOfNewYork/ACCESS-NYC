<?php
/**
 * Template name: Location detail page.
*/

style();
script('main');

if ( ! class_exists( 'Timber' ) ) {
  echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  return;
}

global $params;

$context = Timber::get_context();

$templates = array( 'single-location.twig' );

Timber::render( $templates, $context );
