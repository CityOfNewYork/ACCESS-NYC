<?php
/**
 * Programs Landing Page
 * Controller for the archive view at /programs
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('programs');

$context = Timber::get_context();

/**
 * Retrieve public query variables recognized by WP_Query
 */

$p1 = get_query_var('program_cat', false);
$p2 = get_query_var('pop_served', false);

$context['categories'] = ($p1) ?
  get_term_by('slug', $p1, 'programs') : $p1;

$context['served'] = ($p2) ?
  get_term_by('slug', $p2, 'populations-served') : $p2;

$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();
$context['per_page'] = $wp_query->post_count;
$context['count'] = $wp_query->found_posts;

// $this->alerts = array_filter($context['alerts'], function($p) {
//   return count(array_intersect(array_values($p->custom['location']), ['programs', 'single'])) > 0;
// });

Timber::render('programs/archive.twig', $context);
