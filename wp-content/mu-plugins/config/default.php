<?php

/**
 * All environments config
 */

/**
 * Autoload Composer Dependencies
 */

require_once ABSPATH . '/vendor/autoload.php';

/**
 * Patch for missing WP Security Questions constant
 */

// phpcs:disable
define('wsq_CLASSES', '');
// phpcs:enable
