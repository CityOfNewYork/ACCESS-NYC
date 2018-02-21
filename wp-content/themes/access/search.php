<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$templates = array( 'search.twig', 'archive.twig', 'index.twig' );
$context = Timber::get_context();

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
    wp_redirect( $urlBase . '/?' . $newQuery);
  }
}

if (isset($_GET['program_cat'])) {
  $context['searchCategory'] = get_term_by('slug', $_GET['program_cat'], 'programs');
} else {
  $context['searchCategory'] = '';
}

$context['query'] = get_search_query();
$context['title'] = 'Search results for '. get_search_query();
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();

Timber::render( $templates, $context );
