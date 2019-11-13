<?php
/**
 * Index template
 *
 * A fallback list template used if a more specific template is not available
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

$templates = array( 'index.twig' );

if (is_home()) {
  $context['post'] = Timber::get_post(array(
    'post_type' => 'homepage'
  ));

  $context['homepage_touts'] = Timber::get_posts(array(
    'post_type' => 'homepage_tout',
    'numberposts' => 2
  ));

  $context['homepage_alert'] = Timber::get_post(array(
    'post_type' => 'alert'
  ));

  array_unshift($templates, 'home.twig');
}

Timber::render($templates, $context);
