<?php
/**
 * Screener Login
 * Most of the magic here happens in JavaScript. The only thing we want is a list
 * of program categories.
 */

$context = Timber::get_context();

$templates = array('screener-login.twig');

Timber::render($templates, $context);
