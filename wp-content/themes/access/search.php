<?php

/**
 * Search Results Page
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

$context = Timber::get_context();

preload_fonts($context['language_code']);

/**
 * Search
 */

// This is a patch for search urls getting the language as a parameter for some reason.
$searchUrl = explode('?', $_SERVER['REQUEST_URI']);

$context['searchUrl'] = $searchUrl;

if (isset($_GET['lang'])) {
  wp_parse_str($_SERVER['QUERY_STRING'], $output);

  $queryLang = $output['lang'];

  unset($output['lang']);

  $queryString = $output;

  if (empty($queryLang) == false) {
    $newQuery = '';
    $index = 0;

    foreach ($queryString as $key => $value) {
      $encValue = urlencode($value);

      if ($index == 0) {
        $newQuery = $key . '=' . $encValue;
      } else {
        $newQuery = $newQuery . '&' . $key . '=' . $encValue;
      }

      $index++;
    }

    if ($queryLang != 'en') {
      $urlBase = '/' . $queryLang;
    } else {
      $urlBase = '';
    }

    wp_redirect($urlBase . '/?' . $newQuery);
  }
}

if (isset($_GET['program_cat'])) {
  $context['searchCategory'] = get_term_by('slug', $_GET['program_cat'], 'programs');
} else {
  $context['searchCategory'] = '';
}

$context['query'] = get_search_query();

$context['posts'] = array_map(function($post) {
  return new Controller\Programs($post);
}, Timber::get_posts());

$context['pagination'] = Timber::get_pagination();

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
    return in_array('programs', array_values($p->custom['location']));
  });
}

// Extend alerts with Timber Post Controller
$context['alerts'] = array_map(function($post) {
  return new Controller\Alert($post);
}, $context['alerts']);

/**
 * Add to Schema
 * @author NYC Opportunity
 */

$context['schema'] = encode_schema($context['schema']);

/**
 * Render the view
 */

Timber::render('search.twig', $context);
