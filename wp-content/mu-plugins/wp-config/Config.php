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
 * Constants
 */

// List of protected constants
const PROTECT = ['WP_ENV'];

/**
 * WP Engine
 * is_wpe is defined in the mu-plugins/wpengine-common plugin. is_wpe()
 * returns true only if the site is running on a production environment.
 * @url https://wpengine.com/support/determining-wp-engine-environment/
 */

if (function_exists('is_wpe') && is_wpe()) {
  require_once WP_CONTENT_DIR. '/mu-plugins/config/wpengine.php';
}

if (function_exists('is_wpe_snapshot') && is_wpe_snapshot()) {
  require_once WP_CONTENT_DIR. '/mu-plugins/config/wpengine-snapshot.php';
}

/**
 * Local
 * Define WP_ENV in the root wp-config.php before the wp-settings.php include
 */

if (null !== WP_ENV && file_exists(WP_CONTENT_DIR . '/mu-plugins/config/' . WP_ENV . '.php')) {
  require_once WP_CONTENT_DIR . '/mu-plugins/config/' . WP_ENV . '.php';
}

/**
 * Set configuration to environment variables
 */

if (file_exists(WP_CONTENT_DIR . '/mu-plugins/config/config.yml')) {
  require_once ABSPATH . '/vendor/mustangostang/spyc/Spyc.php';
  $config = Spyc::YAMLLoad(WP_CONTENT_DIR . '/mu-plugins/config/config.yml');
  if (null !== WP_ENV && isset($config[WP_ENV])) {
    $config = $config[WP_ENV];
    if (is_array($config) || is_object($config)) {
      foreach ($config as $key => $value) {
        $name = strtoupper($key);
        if (!in_array($name, PROTECT)) {
          putenv("$name=$value");
          $_ENV[$name] = getenv($name);
          define($name, getenv($name));
        }
      }
    }
  }
}
