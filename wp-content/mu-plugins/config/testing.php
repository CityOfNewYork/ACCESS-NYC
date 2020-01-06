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
 * Remove Stat Collector data logging actions
 * @param   Class  $instance  The instantiated StatCollector class
 */

add_action('init_stat_collector', function($instance) {
  remove_action('drools_request', [$instance, 'droolsRequest'], $instance->priority);
  remove_action('drools_response', [$instance, 'droolsResponse'], $instance->priority);
  remove_action('results_sent', [$instance, 'resultsSent'], $instance->priority);
  remove_action('peu_data', [$instance, 'peuData'], $instance->priority);

  remove_action('wp_ajax_response_update', [$instance, 'responseUpdate'], $instance->priority);
  remove_action('wp_ajax_nopriv_response_update', [$instance, 'responseUpdate'], $instance->priority);
});

/**
 * Allow local development requests
 */

header('Access-Control-Allow-Origin: *');

add_filter('allowed_http_origins', function($origins) {
  $origins[] = 'http://localhost:7000'; // Patterns
  return $origins;
});
