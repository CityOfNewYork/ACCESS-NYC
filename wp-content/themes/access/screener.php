<?php

/**
 * Template name: Eligibility Screener
 *
 * Most of the magic here happens in JavaScript. The
 * only thing we want is a list of program categories.
 *
 * @author Blue State Digital
 */

require_once ACCESS\controller('alert');

/**
 * Enqueue
 */

// Main
enqueue_language_style('style');

// Integrations
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_inline('google-recaptcha');

// Main
enqueue_script('main');
enqueue_script('screener');

/**
 * Context
 */

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
 * Alerts
 */

if (get_field('alert')) {
  $context['alerts'] = get_field('alert');
} else {
  $alerts = Timber::get_posts(array(
    'post_type' => 'alert',
    'posts_per_page' => -1
  ));

  $context['alerts'] = array_filter($alerts, function($p) {
    return in_array('screener', array_values($p->custom['location']));
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Add to Schema
 * @author NYC Opportunity
 */

$context['schema'] = encode_schema($context['schema']);

/**
 * Render the view
 */

Timber::render('screener/screener.twig', $context);
