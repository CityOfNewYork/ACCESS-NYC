<?php
/**
 * Single entry template. Used for posts and other individual content items.
 *
 * To override for a particular post type, create a template named single-[post_type]
 */

style();
script('main');

$context = Timber::get_context();

$post = Timber::get_post();
$templates = array( 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' );
$context['post'] = $post;
Timber::render( $templates, $context );
