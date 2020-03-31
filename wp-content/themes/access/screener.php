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
enqueue_inline('google-recaptcha');
enqueue_script('screener');

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

/**
 * Set Alerts
 */

$alerts = Timber::get_posts(array(
  'post_type' => 'alert',
  'posts_per_page' => -1
));

$context['alerts'] = array_filter($alerts, function($p) {
  return in_array('screener', array_values($p->custom['location']));
});

$templates = array('screener/screener.twig');

Timber::render($templates, $context);
