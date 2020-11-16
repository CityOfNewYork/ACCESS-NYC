<?php

/**
 * 404 or Homepage
 */

require_once ACCESS\controller('programs');
require_once ACCESS\controller('homepage-tout');

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

$template = 'index.twig';


/**
 * Context
 */

$context = Timber::get_context();

$template = 'index.twig';

preload_fonts($context['language_code']);

/**
 * Homepage
 */

if (is_home()) {
  $template = 'home.twig';

  $context['post'] = Timber::get_post(array(
    'post_type' => 'homepage'
  ));

  /**
   * Touts
   */

  $context['homepage_touts'] = array_map(function($post) {
    return new Controller\HomepageTout($post);
  }, Timber::get_posts(array(
    'post_type' => 'homepage_tout',
    'numberposts' => 4
  )));

  /**
   * Get the latest date of touts
   * @author NYC Opportunity
   */

  $context['homepage_touts_latest_update'] = max(array_map(function($post) {
    return $post->post_modified;
  }, $context['homepage_touts']));

  // Alert
  $context['homepage_alert'] = Timber::get_post(array(
    'post_type' => 'alert'
  ));

  // Featured Programs
  $context['featured_programs'] = array_map(function($id) {
    return new Controller\Programs($id);
  }, $context['post']->custom['featured_programs']);

  /**
   * Add to Schema
   * @author NYC Opportunity
   */

  foreach ($context['homepage_touts'] as $t) {
    if ($t->item_scope) {
      $context['schema'][] = $t->getSchema();
    }
  }

  foreach ($context['featured_programs'] as $p) {
    $context['schema'][] = $p->getSchemaTout();
    $context['schema'][] = $p->getSpecialAnnouncementSchema();
  }
}

$context['schema'] = encode_schema($context['schema']);

/**
 * Render the view
 */

Timber::render($template, $context);
