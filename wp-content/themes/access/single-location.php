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
 * Setup schema for Single Location
 */
$context['schema'] = [
  array(
    "@context" => "https://schema.org",
    "@type" => $location->locationType(),
    "name" => $location->title,
    "hasMap" => $location->locationMapURL(),
    "description" => $location->getHelp(),
    "address" => [
      "@type" => "PostalAddress",
      "streetAddress" => $location->address_street,
      "addressLocality" => $location->city,
      "postalCode" => $location->zip
    ],
    "telephone" => $location->getPhone(),
    "sameAs" => $location->website,
    "spatialCoverage" => [
      "type" => "City",
      "name" => "New York"
    ]
  )
];

if ($context['alert_sitewide_schema']) {
  array_push($context['schema'], $context['alert_sitewide_schema']);
}

$context['schema'] = json_encode($context['schema']);

/**
 * Page Meta Description
 */

$context['page_meta_description'] = $location->getPageMetaDescription();


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
