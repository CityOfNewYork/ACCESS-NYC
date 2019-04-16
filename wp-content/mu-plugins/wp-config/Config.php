<?php

/**
 * Plugin Name:  Nyco Wp Config
 * Description:  Autoload configuration based on environment
 * Author:       NYC Opportunity
 * Requirements: The plugin doesn't include dependencies. These should be added
 *               to the root Composer file for the site (composer require ...)
 *               mustangostang/spyc: ^0.6.2
 *               illuminate/encryption: ^5.6
 */

namespace Nyco\WpConfig\Config;

/**
 * Dependencies
 */

use Spyc;
use Illuminate;
use Exception;

/**
 * Constants
 */

const PROTECT = ['WP_ENV']; // List of protected env variables

/**
 * Fuctions
 */

$path = ABSPATH . 'wp-content/mu-plugins/config/';
$secret = file_exists(__DIR__ . '/env.php');

if (file_exists($path . 'config.yml')) {
  $config = Spyc::YAMLLoad($path . 'config.yml');

  // If there is a secret, then assume the file is encrypted
  if ($secret) {
    $secret = require_once(__DIR__ . '/env.php');
    $encrypter = new \Illuminate\Encryption\Encrypter($secret['key']);
  }

  /**
   * Set configuration to environment variables
   */
  if (null !== WP_ENV && isset($config[WP_ENV])) {
    $config = $config[WP_ENV];
    if (is_array($config) || is_object($config)) {
      foreach ($config as $key => $value) {
        $name = strtoupper($key);
        if (!in_array($name, PROTECT)) {
          $decrypted = ($secret) ? $encrypter->decrypt($value) : $value;
          putenv("$name=$decrypted");
          $_ENV[$name] = getenv($name);
          define($name, getenv($name));
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
} else if (null !== WP_DEBUG && WP_DEBUG) {
  throw new Exception(
    "The configuration file or secret could not be found at $path."
  );
} else {
  error_log("The configuration file or secret could not be found at $path.");
}


/**
 * Auto load environment file if it exits
 * Define WP_ENV in the root wp-config.php before the wp-settings.php include
 */

if (null !== WP_ENV && file_exists($path . WP_ENV . '.php')) {
  require_once $path . WP_ENV . '.php';
}
