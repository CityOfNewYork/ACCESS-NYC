<?php
/**
 * Template name: Locations
 *
 * This is the controller for the map at /locations. Most of the code here is used
 * to build the category/program filter list. First we get a list of program
 * categories. Then we loop over those categories to find the related programs.
 * We only want to list Programs that are reverse-related to a location so we
 * need to do a reverse-relationship query and weed out any programs that are
 * not related.
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('locations');

$context = Timber::get_context();

// Get the program categories.
$categories = get_categories(array(
  'post_type' => 'programs',
  'taxonomy' => 'programs',
  'hide_empty' => true
));

$context['filters'] = [];
$context['strings'] = [];

// Set default language.
global $sitepress;
$default_lang = $sitepress->get_default_language();

// For each program category, get each associated program post and add those
// posts to the page context.
foreach ($categories as $category) {
  $catPosts = get_posts(array(
    'post_type' => 'programs',
    'posts_per_page' => -1,
    'tax_query' => array(
      array(
        'taxonomy' => 'programs',
        'terms' => $category->term_id,
      )
    )
  ));

  $filteredPosts = [];

  foreach ($catPosts as $catPost) {
    // 'uid' Is used here to get the ID of the English version of the programs
    // so translations work more smoothly.
    $catPost->uid = icl_object_id($catPost->ID, 'post', true, $default_lang);

    // Do a reverse relationship query to see if there is an associated location
    // to this program. If so, add this program to the $filteredPosts array.
    $relatedLocations = get_posts(array(
      'post_type' => 'location',
      'posts_per_page' => 1,
      'meta_query' => array(
        array(
          'key' => 'programs',
          'value' => $catPost->uid,
          'compare' => 'LIKE'
        )
      )
    ));

    if ($relatedLocations) {
      array_push($filteredPosts, $catPost);
    }
  }

  // If this category has filtered posts, add it to the filter array.
  if (count($filteredPosts) > 0) {
    $context['filters'][] = array(
      'category' => array(
        'name' => $category->name,
        'slug' => $category->slug
      ),
      'programs' => $filteredPosts
    );
  }
}

$templates = array('locations/locations.twig');

$context['post'] = Timber::get_post();

/**
 * Set Alerts
 */

$alerts = Timber::get_posts(array(
  'post_type' => 'alert',
  'posts_per_page' => -1
));

$context['post']->alerts = array_filter($alerts, function($p) {
  return in_array('locations', array_values($p->custom['location']));
});

Timber::render($templates, $context);
