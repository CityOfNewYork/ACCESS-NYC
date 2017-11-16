<?

$context = Timber::get_context();

$get = $_GET;
$get['path'] = $params['share_path'];
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
  // Get post type project
  'post_type' => 'programs',
  'tax_query' => array(
    array(
      'taxonomy'  => 'programs',
      'field'     => 'slug',
      'terms'     => $categories,
      'operator'  => 'NOT IN'
    )
  ),
  // Get all posts
  'posts_per_page' => -1,
  // Filter posts based on the program code in the URL
  'meta_key'    => 'program_code',
  'meta_value'  => $programs
);

$context['selectedPrograms'] = Timber::get_posts( $selectedProgramArgs );
$context['additionalPrograms'] = Timber::get_posts( $additionalProgramArgs );

Timber::render(array('eligibility-results-field.twig'), $context);
