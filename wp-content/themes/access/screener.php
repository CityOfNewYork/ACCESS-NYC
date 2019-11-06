<?php
/**
 * Template name: Eligibility Screener
 *
 * Most of the magic here happens in JavaScript. The
 * only thing we want is a list of program categories.
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('assets/js/main');
enqueue_script('assets/js/screener');

$context = Timber::get_context();

// Get the program categories.
$context['categories'] = get_categories(array(
  'post_type' => 'programs',
  'taxonomy' => 'programs',
  'hide_empty' => false
));

// Add label key for string translation
array_map(function($category) {
  $category->label = $category->name;
  return $category;
}, $context['categories']);

$context['formAction'] = admin_url('admin-ajax.php');

$templates = array( 'screener/screener.twig' );

Timber::render($templates, $context);
