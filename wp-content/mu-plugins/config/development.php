<?php

/**
 * Development environment config
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
 * Disable plugins required for security but slow down logging into the
 * site for development purposes.
 */

deactivate_plugins([
  'google-authenticator/google-authenticator.php',
  'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php'
]);

/**
 * Disable Rollbar because it is only required for remote error monitoring.
 * Uncomment the activate line if you need to test it.
 */

deactivate_plugins('rollbar/rollbar-php-wordpress.php');
// activate_plugin(WP_PLUGIN_DIR . 'rollbar/rollbar-php-wordpress.php');

/**
 * A WordPress plugin for the Whoops Error Framework.
 * This plugin will only work if the development Composer autoloader is
 * being included. A standard composer install will generate an autoloader
 * that will include all dependencies, including dev dependencies. See the
 * README for details on Composer autoloaders.
 */

if (class_exists('\Rarst\wps')) {
  activate_plugin(WP_PLUGIN_DIR . 'wps/wps.php');
} else {
  deactivate_plugins('wps/wps.php');
}

/**
 * Enable the Redis Caching Plugin if we have WP_REDIS_HOST defined in
 * the wp-config.php. Caching will optimize the speed of the site, especially
 * transient caches.
 */

if (null !== WP_REDIS_HOST) {
  activate_plugin(WP_PLUGIN_DIR . 'redis-cache/redis-cache.php');
}

/**
 * Enable Query Monitor for advanced Wordpress Query debug and other tooling.
 */

activate_plugin(WP_PLUGIN_DIR . 'query-monitor/query-monitor.php');

/**
 * Remove Stat Collector data logging actions
 * @param   [class]  $instance  The instantiated StatCollector class
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
