<?php
/**
 * Template name: Location detail page.
*/

global $params;

$context = Timber::get_context();

$templates = array( 'single-location.twig' );

Timber::render( $templates, $context );
