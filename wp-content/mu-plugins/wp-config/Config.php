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

/**
 * Auto update WordPress Admin options
 */

foreach ($_ENV as $key => $value) {
  if (substr($key, 0, 10) === 'WP_OPTION_') {
    update_option(strtolower(str_replace('WP_OPTION_', '', $key)), $value);
  }
}

/**
 * Auto load environment file if it exits
 * Define WP_ENV in the root wp-config.php before the wp-settings.php include
 */

if (null !== WP_ENV && file_exists(WP_CONTENT_DIR . '/mu-plugins/config/' . WP_ENV . '.php')) {
  require_once WP_CONTENT_DIR . '/mu-plugins/config/' . WP_ENV . '.php';
}
