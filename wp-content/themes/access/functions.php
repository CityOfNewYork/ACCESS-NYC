<?php

/**
 * Get Path Helpers
 *
 * @author NYC Opportunity
 */

require_once get_template_directory() . '/lib/paths.php';

/**
 * Theme Functions
 *
 * @link /lib/functions.php
 */

require_once ACCESS\functions();

/**
 * WordPress Gutenberg Blocks
 *
 * @link /blocks/
 * @link https://developer.wordpress.org/block-editor
 */

ACCESS\require_blocks();

/**
 * Shortcodes
 *
 * @link /shortcodes/
 * @link https://codex.wordpress.org/Shortcode
 */

ACCESS\require_shortcodes();

new Shortcode\Accordion();
new Shortcode\CovidScenarios();
new Shortcode\Newsletter();
new Shortcode\NewsletterArchive();

// phpcs:disable
/**
 * Timber Site Controller
 *
 * Only the site controller should be instantiated here. This adds variables
 * (context) to the Timber site object. Other controllers can be instantiated
 * in the views that use them. Ex; locations or programs post types.
 *
 * @link /controllers/
 * @link https://timber.github.io/docs/reference/timber-site/
 * @link https://timber.github.io/docs/guides/extending-timber/#an-example-that-extends-timberpost
 */
// phpcs:enable

require_once ACCESS\controller('site');

new Controller\Site();

// phpcs:disable
/**
 * Extend Timber/Twig
 *
 * Filters extending Timber's implementation of twig can be added in this file.
 *
 * @link /lib/filters.php
 * @link https://timber.github.io/docs/guides/extending-timber/#adding-functionality-to-twig
 */
// phpcs:enable

require_once ACCESS\lib('filters');
