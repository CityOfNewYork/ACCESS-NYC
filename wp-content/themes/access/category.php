<?php
/**
 * Category template
 *
 * The category listing template
 * TODO - This is probably a defunct template and can be deleted.
 *
 */

global $paged;

if (!isset($paged) || !$paged){
    $paged = 1;
}

$context['pagination'] = Timber::get_pagination();

if ( ! class_exists( 'Timber' ) ) {
  echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  return;
}

$context = Timber::get_context();
$context['page'] = $wp_query->get_queried_object();

$postType = 'post_type=programs&numberposts=5';
$context['posts'] = Timber::get_posts($postType);
$context['post'] = new TimberPost();
$templates = array( 'program-landing.twig' );

Timber::render( $templates, $context );
