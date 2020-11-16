<?php

/**
 * Programs Landing Page
 *
 * @author NYC Opportunity
 */

require_once ACCESS\controller('programs');
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
enqueue_script('archive-programs');

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

$context['posts'] = array_map(function($post) {
    return new Controller\Programs($post);
}, Timber::get_posts());

$context['pagination'] = Timber::get_pagination();

$context['per_page'] = $wp_query->post_count;

$context['count'] = $wp_query->found_posts;

/**
 * Query Variables
 */

$p1 = get_query_var('program_cat', false);

$p2 = get_query_var('pop_served', false);

$context['categories'] = ($p1) ?
  get_term_by('slug', $p1, 'programs') : $p1;

$context['served'] = ($p2) ?
  get_term_by('slug', $p2, 'populations-served') : $p2;

/**
 * Get Alerts
 */

$alerts = Timber::get_posts(array(
  'post_type' => 'alert',
  'posts_per_page' => -1
));

$context['alerts'] = array_filter($alerts, function($p) {
  return in_array('programs', array_values($p->custom['location']));
});

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
 * Render View
 */

Timber::render('programs/archive.twig', $context);
