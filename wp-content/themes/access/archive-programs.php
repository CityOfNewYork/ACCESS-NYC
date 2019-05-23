<?php
/**
 * Programs Landing Page
 *
 * Controller for the archive view at /programs
 */

$context = Timber::get_context();

/** Retrieve public query variables recognized by WP_Query */
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

Timber::render('programs/archive.twig', $context);
