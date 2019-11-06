<?php
/**
 * Template name: Field Screener
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('assets/js/field');

$context = Timber::get_context();

// Get the program categories.
$context['categories'] = get_categories(array(
  'post_type' => 'programs',
  'taxonomy' => 'programs',
  'hide_empty' => false
));

$context['WP_ENV'] = environment_string();
$context['formAction'] = admin_url('admin-ajax.php');

$templates = array('field/field.twig');

Timber::render($templates, $context);
