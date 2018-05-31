<?php
/**
 * Field Screener
 */

$context = Timber::get_context();

// Get the program categories.
$context['categories'] = get_categories(array(
  'post_type' => 'programs',
  'taxonomy' => 'programs',
  'hide_empty' => false
));

$context['WP_ENV'] = Notifications\environment_string();
$context['formAction'] = admin_url( 'admin-ajax.php' );

$templates = array('screener-field.twig');

Timber::render($templates, $context);
