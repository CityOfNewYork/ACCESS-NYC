<?php

/**
 * Location detail page.
 */

/**
 * Variables
 */

global $params;

/**
 * Context
 */

$location = new Controller\SingleLocation();

$context = Timber::get_context();
$context['post'] = $location;
$templates = $location->templates();

Timber::render($templates, $context);
