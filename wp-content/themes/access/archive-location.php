<?php
/**
 * Template Name: Location JSON
 * This controller generates the JSON at {{?language_code}}/locations/json. This
 * JSON file has data for all the locations. Currently the generated file is about
 * 380kb and with proper caching is served up reasonably quickly. Copmare this to
 * the native  WP JSON API that can only serve 100 results at a time with an
 * average payload size of 200kb. Still, down the road, this might need to be
 * fleshed out to a more robust JSON endpoint that can accept pagination,
 * latitude, longitude, and program parameters.
 */

$context = Timber::get_context();

$posts = get_posts( array(
  'post_type' => 'location',
  'numberposts' => -1,
  'suppress_filters' => 0
));

// Set default language.
global $sitepress;
$default_lang = $sitepress->get_default_language();

// 'program_uids' Are the IDs of the related programs in the default (English)
// language, used here to solve for possible translation issues.
foreach ($posts as $post) {
  $programs = [];
  $related_programs = get_field('programs', $post->ID);

  if ($related_programs) {
    foreach ($related_programs as $program) {
      array_push($programs, icl_object_id($program->ID, 'post', true, $default_lang));
    }
  }
  $post->link = (ICL_LANGUAGE_CODE == $default_lang) ?
      get_site_url() . '/location/' . $post->post_name :
      get_site_url() . '/' . ICL_LANGUAGE_CODE . '/location/' . $post->post_name;

  $post->program_uids = $programs;
}

$context['posts'] = $posts;

header('Content-type: text/json');
header('Cache-Control: max-age=600');

Timber::render('locations/locations-json.twig', $context, array(600, false));
