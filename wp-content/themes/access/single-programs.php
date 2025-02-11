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
 *
 * @author NYC Opportunity
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
enqueue_inline('google-recaptcha');

// Main
// TODO: Evaluate coverage of individual polyfills and load per browser
enqueue_script('polyfill');
enqueue_script('main');

/**
 * Manual DNS prefetch and preconnect headers that are not added through
 * enqueueing functions above. DNS prefetch is added automatically. Preconnect
 * headers always need to be added manually.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/Performance/dns-prefetch
 *
 * @author NYC Opportunity
 */

add_filter('wp_resource_hints', function($urls, $relation_type) {
  switch ($relation_type) {
    case 'preconnect':
      $urls = array_merge($urls, [
        (defined('S3_UPLOADS_BUCKET'))
          ? '//' . S3_UPLOADS_BUCKET . '.s3.amazonaws.com' : null
      ]);

      break;

    case 'dns-prefetch':
      $urls = array_merge($urls, [
        '//s.webtrends.com',
        '//www.google-analytics.com',
        '//cdnjs.cloudflare.com'
      ]);

      break;
  }

  return $urls;
}, 10, 2);

/**
 * Context
 */

$program = new Controller\Programs();

$context = Timber::get_context();

// If A/B testing is on, add the A/B test variant to the cookies and the context
// The variant cookie is stored for 30 days
if ($context['a_b_testing_on']) {
  if (isset($_COOKIE['ab_test_variant']) and (
      $_COOKIE['ab_test_variant'] == 'a'
      or $_COOKIE['ab_test_variant'] == 'b')) { // Variant cookie must be a valid value
    $context['variant'] = $_COOKIE['ab_test_variant'];
  } else {
    $variant = rand(0, 1) ? 'a' : 'b';
    setcookie('ab_test_variant', $variant, time() + (DAY_IN_SECONDS * 30), COOKIEPATH, COOKIE_DOMAIN);
    $context['variant'] = $variant;
  }
}

// If A/B testing is on, use the Javascript script that matches the variant
if ($context['a_b_testing_on'] && $context['variant'] == 'b') {
  enqueue_script('single-programs-b');
} else {
  enqueue_script('single-programs');
}

preload_fonts($context['language_code']);

/**
 * Gets the url parameter on the page for navigating each section
 *
 * @author Blue State Digital
 */
if (isset($_GET['step'])) {
  $context['step'] = urlencode(
    validate_params('step', urldecode(htmlspecialchars($_GET['step'])))
  );
} else {
  $context['step'] = '';
}

$context['post'] = $program;

/**
 * Add to schema
 *
 * @author NYC Opportunity
 */

$context['schema'][] = $program->getSchema();
$context['schema'][] = $program->getSpecialAnnouncementSchema();
$context['schema'][] = $program->getFaqSchema();
$context['schema'] = encode_schema($context['schema']);

/**
 * Page Meta Description
 */

$context['page_meta_description'] = $program->getPageMetaDescription();

/**
 * Admin AJAX
 */

$context['shareAction'] = admin_url('admin-ajax.php');

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
    $location = (!empty($p->custom['location']))
      ? $p->custom['location'] : [];

    $flags = ['programs', 'single'];

    $location = (!empty($location)) ? $p->custom['location'] : [];

    $intersect = array_intersect(array_values($location), $flags);

    return count($intersect) === count($flags);
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Render the view
 */

if ($context['a_b_testing_on'] && $context['variant'] == 'b') {
  Timber::render('programs-b/single.twig', $context);
} else {
  Timber::render('programs/single.twig', $context);
}
