<?php

/**
 * Single Program
 */

use Config\Paths as asset;

require_once asset\controller('programs');

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

$context = Timber::get_context();

// Gets the url parameter on the page for navigating each section.
if (isset($_GET['step'])) {
  $context['step'] = urlencode(
    validate_params('step', urldecode(htmlspecialchars($_GET['step'])))
  );
} else {
  $context['step'] = '';
}

$query = ($context['step'] !== '') ? '?step=' . $context['step'] : '';

$context['post'] = new Controller\Programs(Timber::get_post());

// Share by email/sms fields.
$context['shareAction'] = admin_url('admin-ajax.php');

$context['shareUrl'] = $post->link . $query;

$context['shareHash'] = \SMNYC\hash($context['shareUrl']);

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

Timber::render('programs/single.twig', $context);
