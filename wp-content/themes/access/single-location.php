<?php

/**
 * Location Detail Page
 * @author Blue State Digital
 */

require_once ACCESS\controller('location');
require_once ACCESS\controller('alert');

/**
 * Enqueue
 * @author NYC Opportunity
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

// Main
enqueue_script('main');

/**
 * Context
 */

$location = new Controller\Location();

$context = Timber::get_context();

$context['post'] = $location;

/**
 * Add to Schema
 * @author NYC Opportunity
 */

$context['schema'][] = $location->getSchema();
$context['schema'] = json_encode($context['schema']);

/**
 * Page Meta Description
 */

$context['page_meta_description'] = $location->getPageMetaDescription();

/**
 * Alerts
 * @author NYC Opportunity
 */

if (get_field('alert')) {
  $context['alerts'] = get_field('alert');
} else {
  $alerts = Timber::get_posts(array(
    'post_type' => 'alert',
    'posts_per_page' => -1
  ));

  $context['alerts'] = array_filter($alerts, function($p) {
    $flags = ['locations', 'single'];
    return count(array_intersect(array_values($p->custom['location']), $flags)) === count($flags);
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Render the view
 */

Timber::render('locations/single.twig', $context);
