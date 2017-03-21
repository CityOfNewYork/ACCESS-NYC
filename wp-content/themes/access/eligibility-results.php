<?php
/**
 * Tempalte Name: Eligibility Screener Results
 * This controls the view at /elgibility/screener. It expects a few URL parameters.
 *   programs: a comma separated list of program codes
 *   categories: a comma separated list of category slugs
 *   date: a UNIX timestamp of when the screener was completed
 *   guid: a unique user ID provided in the Drools Proxy response
 * The screener results list is composed of programs listed in the programs
 * parameter. They are organized into "programs you are interested in" and "other
 * programs you qualify for" based on the categories. In the sidebar of the
 * screener, there are options to share the results via SMS or email via a form.
 * Parameters for that form are created via a hash based on the URL parameters.
 *
 * TODO: This page was originally spec'd to display a line of copy that said
 * "these results are valid as of SOME_DATE" which would be based on the date
 * parameter.
*/
if ( ! class_exists( 'Timber' ) ) {
  echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  return;
}

$context = Timber::get_context();
$programBlob = '';
$categoryBlob = '';

// Gets the URL Parameters for the search value,
if (isset($_GET['programs'])) {
  $programBlob = $_GET['programs'];
  $context['resultPrograms'] = explode(',', $programBlob);
} else {
  $context['resultPrograms'] = '';
}
if (isset($_GET['categories'])) {
  $categoryBlob = $_GET['categories'];
  $context['resultCategories'] = explode(',', $categoryBlob);
} else {
  $context['resultCategories'] = '';
}
if (isset($_GET['date'])) {
  $context['resultDate'] = $_GET['programs'];
} else {
  $context['resultDate'] = '';
}
if (isset($_GET['guid'])) {
  $context['guid'] = $_GET['guid'];
} else {
  $context['guid'] = '';
}

$selectedProgramArgs = array(
  'post_type' => 'programs',
  'tax_query' => array(
    array(
      'taxonomy' => 'programs',
      'field' => 'slug',
      'terms' => $context['resultCategories']
    )
  ),
  'posts_per_page' => -1,
  'meta_key'		=> 'program_code',
  'meta_value'	=> $context['resultPrograms']
);

$additionalProgramArgs = array(
// Get post type project
'post_type' => 'programs',
'tax_query' => array(
  array(
    'taxonomy' => 'programs',
    'field' => 'slug',
    'terms' => $context['resultCategories'],
    'operator' => 'NOT IN'
)
),
// Get all posts
'posts_per_page' => -1,
// Filter posts based on the program code in the URL
'meta_key'		=> 'program_code',
'meta_value'	=> $context['resultPrograms']
);

// Share by email/sms fields.
$context['shareAction'] = admin_url( 'admin-ajax.php' );
$context['shareUrl'] = \SMNYC\get_current_url();
$context['shareHash'] = \SMNYC\hash($context['shareUrl']);
$context['getParams'] = $_GET;

$context['selectedPrograms'] = Timber::get_posts( $selectedProgramArgs );
$context['additionalPrograms'] = Timber::get_posts( $additionalProgramArgs );

$templates = array( 'eligibility-results.twig' );

Timber::render( $templates, $context );
