<?php

// phpcs:disable
/**
 * Plugin Name: NYCO WordPress Config
 * Description: Sets constants and WordPress Options for an installation through a YAML configuration file and autoloads environment specific configuration scripts.
 * Author: NYC Opportunity
 */
// phpcs:enable

if (file_exists(WPMU_PLUGIN_DIR . '/wp-config/Config.php')) {
  require_once WPMU_PLUGIN_DIR . '/wp-config/Config.php';

  new NYCO\Config(file_exists(__DIR__ . '/env.php'));
}
