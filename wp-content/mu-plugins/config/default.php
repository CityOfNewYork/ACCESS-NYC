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

/**
 * Disable XML-RPC methods that require authentication
 * @link https://kinsta.com/blog/wordpress-xml-rpc/
 */

add_filter('xmlrpc_enabled', '__return_false');
