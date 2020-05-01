<?php

/**
 * Programs Landing Page
 *
 * @author NYC Opportunity
 */

require_once Path\controller('programs');

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
enqueue_script('programs');

/**
 * Context
 */

$context = Timber::get_context();

$context['posts'] = array_map(function($post) {
    return new Controller\Programs($post);
}, Timber::get_posts());

$context['pagination'] = Timber::get_pagination();

$context['per_page'] = $wp_query->post_count;

$context['count'] = $wp_query->found_posts;

/**
 * Query Variables
 */

$p1 = get_query_var('program_cat', false);

$p2 = get_query_var('pop_served', false);

$context['categories'] = ($p1) ?
  get_term_by('slug', $p1, 'programs') : $p1;

$context['served'] = ($p2) ?
  get_term_by('slug', $p2, 'populations-served') : $p2;

/**
 * Get Alerts
 */

$alerts = Timber::get_posts(array(
  'post_type' => 'alert',
  'posts_per_page' => -1
));

$context['alerts'] = array_filter($alerts, function($p) {
  return in_array('programs', array_values($p->custom['location']));
});

/**
 * Render View
 */

Timber::render('programs/archive.twig', $context);
