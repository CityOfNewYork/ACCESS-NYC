<?php

/**
 * Send Me NYC Email Template
 */

use Config\Paths as Path;

require_once get_template_directory() . '/lib/paths.php';
require_once Path\controller('single-smnyc-email');

$context = Timber::get_context();
$context['post'] = new Controller\SingleSmnycEmail();

Timber::render($context['post']->templates(), $context);