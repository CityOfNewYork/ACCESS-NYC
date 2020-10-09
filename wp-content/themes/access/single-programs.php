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

// Main
enqueue_script('main');
enqueue_script('single-programs');

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

switch ($context['language_code']) {
  case "kr":
    $fonts = [
      "wp-content/themes/access/assets/fonts/noto-cjk-kr/NotoSansCJKkr-Regular.otf",
      "wp-content/themes/access/assets/fonts/noto-cjk-kr/NotoSansCJKkr-Regular.otf"
    ];
    break;
  case "tc":
    $fonts = [
      "wp-content/themes/access/assets/fonts/noto-cjk-tc/NotoSansCJKtc-Regular.otf",
      "wp-content/themes/access/assets/fonts/noto-cjk-tc/noto-cjk-tc/NotoSansCJKtc-Bold.otf"
    ];
    break;
  case "ar":
    $fonts = [
      "wp-content/themes/access/assets/fonts/noto-ar/NotoNaskhArabic-Regular.ttf",
      "wp-content/themes/access/assets/fonts/noto-ar/NotoNaskhArabic-Bold.ttf"
    ];
    break;
  case "ur":
    $fonts = [
      "wp-content/themes/access/assets/fonts/noto-ur/NotoNastaliqUrdu-Regular.ttf"
    ];
    break;
  default:
    $fonts = [
      "wp-content/themes/access/assets/fonts/noto-serif/NotoSerif.woff2",
      "wp-content/themes/access/assets/fonts/noto-sans/NotoSans-Italic.woff2",
      "wp-content/themes/access/assets/fonts/noto-sans/NotoSans-Bold.woff2",
      "wp-content/themes/access/assets/fonts/noto-sans/NotoSans-BoldItalic.woff2",
    ];
}

add_action('wp_head', function() use ($fonts) {
  $preload_links = array_map(function($font_path) {
   return   '<link rel="preload" href=' .$font_path. ' as="font" crossorigin>';
  }, $fonts);

  $output = implode(" ", $preload_links);
  echo $output;
}, 2);

/**
 * Gets the url parameter on the page for navigating each section
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
