<?php

/**
 * All environments config
 */

/**
 * Autoload Composer Dependencies
 */

require_once ABSPATH . '/vendor/autoload.php';

/**
 * Whoops PHP Error Handler
 * @link https://github.com/filp/whoops
 */

if (class_exists('Whoops\Run')) {
  $whoops = new Whoops\Run;
  $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
  $whoops->register();
}

/**
 * Patch for missing WP Security Questions constant
 */

// phpcs:disable
define('wsq_CLASSES', '');
// phpcs:enable
