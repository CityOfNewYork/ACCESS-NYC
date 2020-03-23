<?php

/**
 * Testing environment config for *almost* live testing
 */

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
 * Shorthand for debug logging
 *
 * @param   String   $str     The string to log.
 * @param   Boolean  $return  Wether to make it human readable.
 */
// phpcs:disable
function debug($str, $return = true) {
  $backtrace = debug_backtrace()[0];

  error_log(
    var_export($str, $return) . " " .
    $backtrace['file'] . ':' . $backtrace['line'] . "\r\n"
  );
}
// phpcs:enable

/**
 * Include the plugins module
 */

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Activate plugins
 */

activate_plugin('google-authenticator/google-authenticator.php');
activate_plugin('limit-login-attempts-reloaded/limit-login-attempts-reloaded.php');
activate_plugin('rollbar/rollbar-php-wordpress.php');

/**
 * Enable the Redis Caching Plugin if we have WP_REDIS_HOST defined in
 * the wp-config.php. Caching will optimize the speed of the site, especially
 * transient caches.
 */

if (null !== WP_REDIS_HOST) {
  activate_plugin('redis-cache/redis-cache.php');
}

/**
 * Enable Query Monitor for advanced Wordpress Query debug and other tooling.
 */

activate_plugin('query-monitor/query-monitor.php');

/**
 * Allow local development requests
 */

header('Access-Control-Allow-Origin: *');

add_filter('allowed_http_origins', function($origins) {
  $origins[] = 'http://localhost:7000'; // Patterns
  return $origins;
});
