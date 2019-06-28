<?php

/**
 * Development environment config
 */

// Include the plugins module
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
 */

deactivate_plugins('rollbar/rollbar-php-wordpress.php');

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
 * Remove Stat Collector Actions
 */

add_action('init_stat_collector', function() {
  global $stat_collector;

  remove_action('drools_request', [$stat_collector, 'droolsRequest'], 10, 2);
  remove_action('drools_response', [$stat_collector, 'droolsResponse'], 10, 2);
  remove_action('results_sent', [$stat_collector, 'resultsSent'], 10, 5);
  remove_action('peu_data', [$stat_collector, 'peuData'], 10, 3);

  remove_action('wp_ajax_response_update', [$stat_collector, 'responseUpdate']);
  remove_action('wp_ajax_nopriv_response_update', [$stat_collector, 'responseUpdate']);
});
