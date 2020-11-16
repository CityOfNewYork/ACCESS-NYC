<?php

/**
 * Location Detail Page
 *
 * @author Blue State Digital
 */

require_once ACCESS\controller('location');
require_once ACCESS\controller('alert');

/**
 * Enqueue
 *
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
// TODO: Evaluate coverage of individual polyfills and load per browser
enqueue_script('polyfill');
enqueue_script('main');
enqueue_script('single-location');

/**
 * Manual DNS prefetch and preconnect headers that are not added through
 * enqueueing functions above. DNS prefetch is added automatically. Preconnect
 * headers always need to be added manually.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/Performance/dns-prefetch
 *
 * @author NYC Opportunity
 */

add_filter('wp_resource_hints', function($urls, $relation_type) {
  switch ($relation_type) {
    case 'preconnect':
      $urls = array_merge($urls, [
        '//cdnjs.cloudflare.com',
        '//maps.gstatic.com',
        '//maps.googleapis.com'
      ]);

      break;

    case 'dns-prefetch':
      $urls = array_merge($urls, [
        '//s.webtrends.com',
        '//www.google-analytics.com',
        '//cdnjs.cloudflare.com',
        '//fonts.googleapis.com',
        '//maps.gstatic.com',
        '//maps.googleapis.com'
      ]);

      break;
  }

  return $urls;
}, 10, 2);

/**
 * Context
 */

$location = new Controller\Location();

$context = Timber::get_context();

$context['post'] = $location;

preload_fonts($context['language_code']);

/**
 * Add to Schema
 * @author NYC Opportunity
 */

$context['schema'][] = $location->getSchema();
$context['schema'] = encode_schema($context['schema']);

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
