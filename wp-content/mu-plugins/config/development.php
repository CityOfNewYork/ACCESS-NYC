<?php

/**
 * Development environment config
 */

// Disable plugins
require_once ABSPATH . 'wp-admin/includes/plugin.php';

deactivate_plugins([
  'google-authenticator/google-authenticator.php',
  'rollbar/rollbar-php-wordpress.php'
]);

activate_plugin(WP_PLUGIN_DIR . 'wps/wps.php');
activate_plugin(WP_PLUGIN_DIR . 'query-monitor/query-monitor.php');
