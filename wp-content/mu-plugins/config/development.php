<?php

/**
 * Development environment config
 */

// Disable plugins
require_once ABSPATH . 'wp-admin/includes/plugin.php';
deactivate_plugins('google-authenticator/google-authenticator.php');
deactivate_plugins('rollbar/rollbar-php-wordpress.php');

// Discourage search engines
// @url https://codex.wordpress.org/Option_Reference#Privacy
update_option('blog_public', 0);

// Auto update WordPress Admin options
foreach ($_ENV as $key => $value) {
  if (substr($key, 0, 10) === 'WP_OPTION_') {
    update_option(strtolower(str_replace('WP_OPTION_', '', $key)), $value);
  }
}
