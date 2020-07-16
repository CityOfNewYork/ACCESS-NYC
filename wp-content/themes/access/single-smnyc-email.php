<?php

/**
 * Send Me NYC Email Template
 *
 * @author NYC Opportunity
 */

require_once get_template_directory() . '/lib/paths.php';
require_once ACCESS\controller('smnyc-email');

/**
 * Context
 */

$context = Timber::get_context();

$context['post'] = new Controller\SmnycEmail();

/**
 * Render the view
 */

Timber::render($context['post']->templates(), $context);
