<?php
/**
 * Single entry template. Used for posts and other individual content items.
 *
 * To override for a particular post type, create a template named single-[post_type]
 */

$context = Timber::get_context();

$templates = array( 'single-guide.twig' );
Timber::render( $templates, $context );
