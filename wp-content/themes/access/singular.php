<?php
/**
 * Single entry template. Used for posts and other individual content items.
 *
 * To override for a particular post type, create a template named single-[post_type]
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('main');

$context = Timber::get_context();
$post = Timber::get_post();

$templates = array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' );
$context['post'] = $post;

/**
 * Set Alerts
 */

$alerts = Timber::get_posts(array(
  'post_type' => 'alert',
  'posts_per_page' => -1
));

$context['alerts'] = array_filter($alerts, function($p) {
  return in_array('pages', array_values($p->custom['location']));
});

Timber::render($templates, $context);
