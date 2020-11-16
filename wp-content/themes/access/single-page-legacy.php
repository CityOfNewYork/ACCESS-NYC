<?php

/**
 * Template name: Legacy Page
 *
 * @author NYC Opportunity
 */

require_once ACCESS\controller('alert');
require_once ACCESS\controller('page');

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
enqueue_inline('google-translate-element');

// Main
// TODO: Evaluate coverage of individual polyfills and load per browser
enqueue_script('polyfill');
enqueue_script('main');

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
        (defined('S3_UPLOADS_BUCKET'))
          ? '//' . S3_UPLOADS_BUCKET . '.s3.amazonaws.com' : null
      ]);

      break;

    case 'dns-prefetch':
      $urls = array_merge($urls, [
        '//s.webtrends.com',
        '//www.google-analytics.com',
        '//cdnjs.cloudflare.com'
      ]);

      break;
  }

  return $urls;
}, 10, 2);

/**
 * Context
 */

$context = Timber::get_context();

preload_fonts($context['language_code']);

$post = Timber::get_post();

$page = new Controller\Page($post);

$context['post'] = $page;

/**
 * Set Alerts
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
    return in_array('pages', array_values($p->custom['location']));
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Show Google Translate
 * @author NYC Opportunity
 */

$context['google_translate_element'] = true;

/**
 * Add to Schema
 * @author NYC Opportunity
 */

$context['schema'][] = $page->getSchema();
$context['schema'] = encode_schema($context['schema']);

/**
 * Render Template
 */

Timber::render('single-page-legacy.twig', $context);
