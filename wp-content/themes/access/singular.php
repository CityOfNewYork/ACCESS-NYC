<?php

/**
 * Single Entries (w/o templates)
 *
 * @author NYC Opportunity
 */

require_once ACCESS\controller('alert');
require_once ACCESS\controller('page');

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
enqueue_inline('google-translate-element');

// Main
enqueue_script('main');

/**
 * Context
 */

$context = Timber::get_context();

$post = Timber::get_post();

$page = new Controller\Page($post);

$context['post'] = $page;

/**
 * Set Alerts
 */

if (get_field('alert')) {
  $context['alerts'] = get_field('alert');
} else {
  $alerts = Timber::get_posts(array(
    'post_type' => 'alert',
    'posts_per_page' => -1
  ));

  $context['alerts'] = array_filter($alerts, function($p) {
    return in_array('pages', array_values($p->custom['location']));
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Show Google Translate
 */

$context['google_translate_element'] = true;

/**
 * Set Schema
 */

$context['schema'][] = $page->getSchema();
$context['schema'] = json_encode(array_filter($context['schema']));

/**
 * Render the view
 */

Timber::render('singular.twig', $context);
