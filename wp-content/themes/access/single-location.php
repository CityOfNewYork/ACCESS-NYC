<?php

/**
 * Location detail page
 */

use Config\Paths as Path;

require_once get_template_directory() . '/lib/paths.php';
require_once Path\controller('single-location');

/**
 * Context
 */

$location = new Controller\SingleLocation();
$context = Timber::get_context();
$context['post'] = $location;
$templates = $location->templates();

Timber::render($templates, $context);
