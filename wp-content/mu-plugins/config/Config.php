<?php

/**
 * Plugin Name: Config
 * Description: Autoload configuration based on environment
 * Author:      NYC Opportunity
 */

namespace Config;

/**
 * Dependencies
 */

use Spyc;

/**
 * WP Engine
 * is_wpe is defined in the mu-plugins/wpengine-common plugin. is_wpe()
 * returns true only if the site is running on a production environment.
 * @url https://wpengine.com/support/determining-wp-engine-environment/
 */

if (function_exists('is_wpe') && is_wpe()) {
  require_once(plugin_dir_path( __FILE__ ) . '/wpengine.php');
}

if (function_exists('is_wpe_snapshot') && is_wpe_snapshot()) {
  require_once(plugin_dir_path( __FILE__ ) . '/wpengine-snapshot.php');
}

/**
 * Local
 * Define WP_ENV in the root wp-config.php before the wp-settings.php include
 */

if (null !== WP_ENV && WP_ENV === 'development') {
  require_once(plugin_dir_path( __FILE__ ) . '/development.php');
}

/**
 * Set configuration to environment variables
 */

if (file_exists(plugin_dir_path( __FILE__ ) . '/config.yml')) {
  require_once WP_CONTENT_DIR . '/vendor/mustangostang/spyc/Spyc.php';
  $config = Spyc::YAMLLoad(plugin_dir_path( __FILE__ ) . '/config.yml');
  if (isset($config[WP_ENV])) {
    $config = $config[WP_ENV];
    foreach ($config as $key => $value) {
      $name = strtoupper($key);
      putenv("$name=$value");
      $_ENV[$name] = getenv($name);
      define($name, getenv($name));
    }
  }
}
