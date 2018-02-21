<?php
/**
 * Index template
 *
 * A fallback list template used if a more specific template is not available
 *
 */

$context = Timber::get_context();

$templates = array( 'index.twig' );

if ( is_home() ) {
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

  array_unshift( $templates, 'home.twig' );
}

Timber::render( $templates, $context );
