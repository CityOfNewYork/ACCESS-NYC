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
 * The Config Class
 */
class Config
{

  const PROTECT = ['WP_ENV']; // List of protected env variables

  /** The path to the configuration directory */
  public $path = ABSPATH . 'wp-content/mu-plugins/config/';

  /** Placeholder for environment variables */
  private $envs = array();

  public function __construct($secret = false) {
    $this->secret = $secret;

    if (file_exists($this->path . 'config.yml')) {
      $config = Spyc::YAMLLoad($this->path . 'config.yml');

      // If there is a secret, then assume the file is encrypted
      if ($secret) {
        $secret = require_once(__DIR__ . '/env.php');
        $encrypter = new \Illuminate\Encryption\Encrypter($secret['key']);
      }

      /**
       * Set configuration to environment variables
       */

      // Extract env specific variables
      if (null !== WP_ENV && isset($config[WP_ENV])) {
        $this->envs = $config[WP_ENV];
      }

      // Merge env variables over root variables
      $this->set(array_merge($config, $this->envs));

      /**
       * Auto update WordPress Admin options
       */
      foreach ($_ENV as $key => $value) {
        if (substr($key, 0, 10) === 'WP_OPTION_') {
          update_option(
            strtolower(str_replace('WP_OPTION_', '', $key)),
            $value
          );
        }
      }
    } elseif (null !== WP_DEBUG && WP_DEBUG) {
      throw new Exception(
        "The configuration file or secret could not be found at $this->path."
      );
    } else {
      error_log(
        "The configuration file or secret could not be found at $this->path."
      );
    }

    /**
     * Auto load environment file if it exits
     * Define WP_ENV in the root wp-config.php before the wp-settings.php include
     */
    if (null !== WP_ENV && file_exists($this->path . WP_ENV . '.php')) {
      require_once $this->path . WP_ENV . '.php';
    }
  }

  /**
   * Set configuration to environment variables
   * @param array $config An array containing environment variables to set.
   *                      Variables containing arrays or objects are not set.
   */
  private function set($config) {
    foreach ($config as $key => $value) {
      if (!is_array($value) && !is_object($value)) {
        $name = strtoupper($key);

        if (!in_array($name, Config::PROTECT)) {
          $decrypted = ($this->secret) ? $encrypter->decrypt($value) : $value;
          putenv("$name=$decrypted");
          $_ENV[$name] = $decrypted;
          define($name, $decrypted);
        }
      }
    }
  }
}
