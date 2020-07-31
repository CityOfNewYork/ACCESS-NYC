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

$context['schema'] = [
  array(
    '@context' => 'https://schema.org',
    '@type' => 'GovernmentService',
    'name' => $program->name,
    'alternateName' => $program->plain_language_program_name,
    'datePosted' => $program->post_modified,
    'url' => $program->get_permalink,
    'serviceType' => $program->category['name'],
    'serviceOperator' => array(
      '@type' => 'GovernmentOrganization',
      'name' => $program->government_agency
    ),
    'availableChannel' => array(
      '@type' => 'ServiceChannel',
      'description' => $program->get_field(accordion)
    ),
    'spatialCoverage' => array(
      'type' => 'City',
      'name' => 'New York'
    ),
    'description' => $program->get_field('program_description'),
    'disambiguatingDescription' => $program->disambiguatingDescription()
  )
];

if ($program->getItemScope() === 'SpecialAnnouncement') {
  $special_announcement = array(
    '@context' => 'https://schema.org',
    '@type' => 'SpecialAnnouncement',
    'name' => $program->program_name,
    'category' => 'https://www.wikidata.org/wiki/Q81068910',
    'datePosted' => $program->post_modified,
    'expires' => ($program->custom['program_status_clear_date'] ?
                    $program->custom['program_status_clear_date'] : ''),
    'governmentBenefitsInfo' => array(
      '@type' => 'GovernmentService',
      'name' => $program->program_name,
      'url' => $program->structured_data_url,
      'provider' => array(
        '@type' => 'GovernmentOrganization',
        'name' => $program->government_agency
      ),
      'audience' => array(
        '@type' => 'Audience',
        'name' => $program->audience
      ),
      'serviceType' => $program->category['name']
    ),
    'serviceOperator' => array(
      '@type' => 'GovernmentOrganization',
      'name' => $program->government_agency
    ),
    'spatialCoverage' => array(
      'type' => 'City',
      'name' => 'New York'
    )
  );
  array_push($context['schema'], $special_announcement);
}

/**
* The array $questions has a set of elements that are the questions to be added
* to the $faq variable which will be added to the schema as the `FAQPage`
* section.
*/
$questions = [
  array(
    '@type' => 'Question',
    'name' => "How does $program->program_name work?",
    'acceptedAnswer' => array(
      '@type' => 'Answer',
      'text' => $program->faqAnswer('field_58912c1a8a81b')
    )
  ),
  array(
    '@type' => 'Question',
    'name' => "Am I eligible for $program->program_name?",
    'acceptedAnswer' => array(
      '@type' => 'Answer',
      'text' => $program->faqAnswer('field_58912c1a8a82d')
    )
  ),
  array(
    '@type' => 'Question',
    'name' => "What do I need in order to apply to $program->program_name?",
    'acceptedAnswer' => array(
      '@type' => 'Answer',
      'text' => $program->faqAnswer('field_589de18fca4e0')
    )
  ),
  array(
    '@type' => 'Question',
    'name' => "How do I Apply to $program->program_name?",
    'acceptedAnswer' => array(
      '@type' => 'Answer',
      'text' => join('', [$program->faqAnswer('field_58912c1a8a850'),
                          $program->faqAnswer('field_58912c1a8a885'),
                          $program->faqAnswer('field_58912c1a8a900'),
                          $program->faqAnswer('field_58912c1a8a8cb')])
    )
  )
];

if ($program->addQuestionsToSchemaFaq($questions)) {
  $faq = array(
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $program->addQuestionsToSchemaFaq($questions)
  );

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
