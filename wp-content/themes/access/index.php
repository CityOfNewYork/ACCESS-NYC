<?php

/**
 * 404 or Homepage
 */

require_once ACCESS\controller('programs');
require_once ACCESS\controller('homepage-tout');

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

// Main
enqueue_script('main');

/**
 * Context
 */

$context = Timber::get_context();

$template = 'index.twig';

/**
 * Homepage
 */

if (is_home()) {
  $template = 'home.twig';

  $context['post'] = Timber::get_post(array(
    'post_type' => 'homepage'
  ));

  $context['homepage_touts'] = Timber::get_posts(array(
    'post_type' => 'homepage_tout',
    'numberposts' => 4
  ));

  $context['homepage_touts'] = array_map(function($post) {
    return new Controller\HomepageTout($post);
  }, Timber::get_posts(array(
    'post_type' => 'homepage_tout',
    'numberposts' => 4
  )));

  $context['homepage_touts_latest_update'] = max(array_map(function($post) {
    return $post->post_modified;
  }, $context['homepage_touts']));

  $context['homepage_alert'] = Timber::get_post(array(
    'post_type' => 'alert'
  ));

  $context['featured_programs'] = array_map(function($id) {
    return new Controller\Programs($id);
  }, $context['post']->custom['featured_programs']);
}

/**
 * Setup schema for homepage touts
 */

$context['schema'] = array_map(function($tout) {
  if ($tout->item_scope) {
    return array(
      '@context' => 'https://schema.org',
      '@type' => $tout->item_scope,
      'name' => $tout->tout_title,
      'newsUpdatesAndGuidelines' => $tout->link_url,
      'datePosted' => $tout->post_modified,
      'expires' => $tout->custom['tout_status_clear_date'],
      'text' => $tout->link_text,
      'category' => 'https://www.wikidata.org/wiki/Q81068910',
      'spatialCoverage' => [
        'type' => 'City',
        'name' => 'New York'
      ]
    );
  }
}, $context['homepage_touts']);

$context['schema'] = array_filter($context['schema']);

if ($context['alert_sitewide_schema']) {
  array_push($context['schema'], $context['alert_sitewide_schema']);
}

$context['schema'] = json_encode($context['schema']);

/**
 * Render the view
 */

Timber::render($template, $context);
