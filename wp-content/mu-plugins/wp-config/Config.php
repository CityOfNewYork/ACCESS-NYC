<?php

namespace NYCO;

/**
 * Dependencies
 */

use Spyc;
use Illuminate;
use Exception;

/**
 * The Config Class
 */
class Config {
  const PROTECT = ['WP_ENV']; // List of protected env variables

  /** The path to the configuration directory */
  public $path = ABSPATH . 'wp-content/mu-plugins/config/';

  private $config = 'config.yml';

  private $env = 'env.php';

  private $default = 'default.php';

  /** Placeholder for environment variables */
  private $envs = array();

  /** List of allowable filters */
  private $allowed_filters = [
    '__return_false',
    '__return_true',
    '__return_empty_array',
    '__return_zero',
    '__return_null',
    '__return_empty_string'
  ];

  public function __construct($secret = false) {
    $this->secret = $secret;

    /**
     * Auto load default environment
     */
    if (file_exists($this->path . $this->default)) {
      require_once $this->path . $this->default;
    }

    /**
     * Auto load environment file if it exits
     * Define WP_ENV in the root wp-config.php before the wp-settings.php include
     */
    if (defined('WP_ENV') && file_exists($this->path . WP_ENV . '.php')) {
      require_once $this->path . WP_ENV . '.php';
    }

    if (file_exists($this->path . $this->config)) {
      $config = Spyc::YAMLLoad($this->path . $this->config);

      // If there is a secret, then assume the file is encrypted
      if ($secret) {
        $secret = require_once(__DIR__ . '/' . $this->env);
        $encrypter = new \Illuminate\Encryption\Encrypter($secret['key']);
      }

      /**
       * Set configuration to environment variables
       */

      // Extract env specific variables
      if (defined('WP_ENV') && isset($config[WP_ENV])) {
        $this->envs = $config[WP_ENV];
      }

      // Merge env variables over root variables
      $this->set(array_merge($config, $this->envs));
    } elseif (defined('WP_DEBUG') && WP_DEBUG) {
      throw new Exception(
        "The configuration file or secret could not be found at $this->path."
      );
    } else {
      error_log(
        "The configuration file or secret could not be found at $this->path."
      );
    }

    /**
     * A hook for the initiation of the plugin
     */
    do_action('nyco_wp_config_loaded', $this);
  }

  /**
   * Set configuration to environment variables
   *
   * @param  Array  $config  An array containing environment variables to set.
   *                         Variables containing arrays or objects are not set.
   */
  private function set($config) {
    foreach ($config as $key => $value) {
      if (!is_array($value) && !is_object($value)) {
        $name = strtoupper($key);

        if (!in_array($name, Config::PROTECT)) {
          $decrypted = ($this->secret) ? $encrypter->decrypt($value) : $value;

          /**
           * Define the constant
           */

          define($name, $decrypted);

          /**
           * Update WordPress Admin option if it is an option
           */

          if (substr($name, 0, 10) === 'WP_OPTION_') {
            update_option(
              strtolower(str_replace('WP_OPTION_', '', $name)), $decrypted
            );
          }

          /**
           * Create a filter if the option is a filter
           */

          if (substr($name, 0, 10) === 'WP_FILTER_' && in_array($decrypted, $allowed_filters)) {
            add_filter(strtolower(str_replace('WP_FILTER_', '', $name)), $decrypted);
          }
        }
      }
    }
  }
}
