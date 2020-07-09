<?php

/**
 * Single Program
 *
 * @author Blue State Digital
 */

require_once Path\controller('programs');
require_once Path\controller('alert');

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

$context['schema'] = [
  array(
    "@context" => "https://schema.org",
    "@type" => "GovernmentService",
    "name" => $program->name,
    "alternateName" => $program->plain_language_program_name,
    "datePosted" => $program->post_modified,
    "expires" => $program->custom['program_status_clear_date'],
    "url" => $program->get_permalink,
    "serviceType" => $program->category['name'],
    "serviceOperator" => array(
      "@type" => "GovernmentOrganization",
      "name" => $program->government_agency
    ),
    "availableChannel" => array(
      "@type" => "ServiceChannel",
      "description" => $program->get_field(accordion)
    ),
    "spatialCoverage" => array(
      "type" => "City",
      "name" => "New York"
    ),
    "description" => $program->get_field('program_description'),
    "disambiguatingDescription" => $program->disambiguatingDescription()
  )
];

if ($program->getItemScope() === 'SpecialAnnouncement') {
  $special_announcement = array(
    "@context" => "https://schema.org",
    "@type" => "SpecialAnnouncement",
    "name" => $program->program_name,
    "category" => "https://www.wikidata.org/wiki/Q81068910",
    "governmentBenefitsInfo" => array(
      "@type" => "GovernmentService",
      "name" => $program->program_name,
      "url" => $program->structured_data_url,
      "provider" => array(
        "@type" => "GovernmentOrganization",
        "name" => $program->government_agency
      ),
      "audience" => array(
        "@type" => "Audience",
        "name" => $program->audience
      ),
      "serviceType" => $program->category['name']
    ),
    "serviceOperator" => array(
      "@type" => "GovernmentOrganization",
      "name" => $program->government_agency
    ),
    "spatialCoverage" => array(
      "type" => "City",
      "name" => "New York"
    )
  );
  array_push($context['schema'], $special_announcement);
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
