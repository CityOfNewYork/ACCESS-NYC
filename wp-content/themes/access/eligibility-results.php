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
$query = array();

// Gets the URL Parameters for the search value,
if (isset($_GET['programs'])) {
  $programBlob = urlencode(
    validate_params('programs', urldecode(htmlspecialchars($_GET['programs'])))
  );
  $context['resultPrograms'] = explode(',', $programBlob);
  $query['programs'] = $programBlob;
} else {
  $context['resultPrograms'] = '';
}

if (isset($_GET['categories'])) {
  $categoryBlob = urlencode(
    validate_params('categories', urldecode(htmlspecialchars($_GET['categories'])))
  );
  $context['resultCategories'] = explode(',', $categoryBlob);
  $query['categories'] = $categoryBlob;
} else {
  $context['resultCategories'] = '';
}

if (isset($_GET['date'])) {
  $dateBlob = urlencode(
    validate_params('date', urldecode(htmlspecialchars($_GET['date'])))
  );
  $context['resultDate'] = $dateBlob;
  $query['date'] = $dateBlob;
} else {
  $context['resultDate'] = '';
}

if (isset($_GET['guid'])) {
  $guidBlob = urlencode(
    validate_params('guid', urldecode(htmlspecialchars($_GET['guid'])))
  );
  $context['guid'] = $guidBlob;
  $query['guid'] = $guidBlob;
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

// Rebuild the query from entity stripped params
$get = $query;
$query = http_build_query($query);
$query = (isset($query)) ? '?'.$query : '';

// Share by email/sms fields.
$context['shareAction'] = admin_url( 'admin-ajax.php' );
$context['shareUrl'] = $params['link'].$query;
$context['shareHash'] = \SMNYC\hash($context['shareUrl']);
$context['getParams'] = $get; // pass safe parameters

$context['selectedPrograms'] = Timber::get_posts( $selectedProgramArgs );
$context['additionalPrograms'] = Timber::get_posts( $additionalProgramArgs );

$templates = array( 'eligibility-results.twig' );

Timber::render( $templates, $context );
