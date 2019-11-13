<?php
/**
 * Template name: Field Screener Results
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('field');

$context = Timber::get_context();

$get = $_GET;
$get['path'] = '/eligibility/results/';
$shareData = share_data($get);

// Share by email/sms fields.
$context['action'] = admin_url('admin-ajax.php');
$context['url'] = $shareData['url'];
$context['hash'] = $shareData['hash'];
$context['guid'] = (isset($shareData['query']['guid'])) ?
$shareData['query']['guid'] : '';

$categories = (isset($shareData['query']['categories'])) ?
explode(',', $shareData['query']['categories']) : '';

$programs = (isset($shareData['query']['programs'])) ?
explode(',', $shareData['query']['programs']) : '';

$selectedProgramArgs = array(
  'post_type' => 'programs',
  'tax_query' => array(
    array(
      'taxonomy'  => 'programs',
      'field'     => 'slug',
      'terms'     => $categories
    )
  ),
  'posts_per_page' => -1,
  'meta_key'       => 'program_code',
  'meta_value'     => $programs
);

$additionalProgramArgs = array(
  'post_type' => 'programs',
  'tax_query' => array(
    array(
      'taxonomy'  => 'programs',
      'field'     => 'slug',
      'terms'     => $categories,
      'operator'  => 'NOT IN'
    )
  ),
  'posts_per_page' => -1,
  'meta_key'    => 'program_code',
  'meta_value'  => $programs
);

$context['programs'] = implode(',', $programs);
$context['selectedPrograms'] = Timber::get_posts($selectedProgramArgs);
$context['additionalPrograms'] = Timber::get_posts($additionalProgramArgs);
$context['WP_ENV'] = environment_string();

Timber::render(array('field/results.twig'), $context);
