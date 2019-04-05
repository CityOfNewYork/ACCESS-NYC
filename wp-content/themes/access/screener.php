<?php
/**
 * Template name: Eligiblity Screener.
 * Most of the magic here happens in JavaScript. The only thing we want is a list
 * of program categories.
 */

$context = Timber::get_context();

// Get the program categories.
$context['categories'] = get_categories(array(
  'post_type' => 'programs',
  'taxonomy' => 'programs',
  'hide_empty' => false
));

$context['formAction'] = admin_url( 'admin-ajax.php' );

$templates = array( 'screener/screener.twig' );

Timber::render( $templates, $context );
