<?php

/**
 * Autoload configuration based on environment
 */


/**
 * WP Engine
 * is_wpe is defined in the mu-plugins/wpengine-common plugin. is_wpe()
 * returns true only if the site is running on a production environment.
 * @url https://wpengine.com/support/determining-wp-engine-environment/
 */

if (function_exists('is_wpe') && is_wpe()) {
  require_once(get_template_directory() . '/config/wpengine.php');
}

if (function_exists('is_wpe_snapshot') && is_wpe_snapshot()) {
  require_once(get_template_directory() . '/config/wpengine-snapshot.php');
}


/**
 * Local
 * Define WP_ENV in the root wp-config.php before the wp-settings.php include
 */

if (null !== WP_ENV && WP_ENV === 'development') {
  require_once(get_template_directory() . '/config/development.php');
}