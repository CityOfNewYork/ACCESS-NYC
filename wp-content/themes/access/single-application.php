<?php
/**
 * Single entry template. Used for posts and other individual content items.
 *
 * To override for a particular post type, create a template named single-[post_type]
 */

$context = Timber::get_context();

$context['title'] = $params['title'];
$context['seamless'] = $params['seamless'];

// $post = Timber::get_post();
$templates = array( 'single-application.twig' );
// $context['post'] = $post;
Timber::render( $templates, $context );
