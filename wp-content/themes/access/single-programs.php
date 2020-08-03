<?php

/**
 * Single Program
 *
 * @author Blue State Digital
 */

require_once ACCESS\controller('programs');
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

// Main
enqueue_script('programs');

/**
 * Context
 */

$program = new Controller\Programs();

$context = Timber::get_context();

// Gets the url parameter on the page for navigating each section.
if (isset($_GET['step'])) {
  $context['step'] = urlencode(
    validate_params('step', urldecode(htmlspecialchars($_GET['step'])))
  );
} else {
  $context['step'] = '';
}

$context['post'] = $program;

/**
 * Schema
 */
$context['schema'] = $program->getSchema();

if ($program->getItemScope() === 'SpecialAnnouncement') {
  $special_announcement = $program->getSpecialAnnouncementSchema();
  array_push($context['schema'], $special_announcement);
}

if ($program->getFaqSchema()) {
  $faq = $program->getFaqSchema();
  array_push($context['schema'], $faq);
}

if ($context['alert_sitewide_schema']) {
  array_push($context['schema'], $context['alert_sitewide_schema']);
}

$context['schema'] = json_encode($context['schema']);

/**
 * Page Meta Description
 */

$context['page_meta_description'] = $program->getPageMetaDescription();

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
    $flags = ['programs', 'single'];
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

Timber::render('programs/single.twig', $context);
