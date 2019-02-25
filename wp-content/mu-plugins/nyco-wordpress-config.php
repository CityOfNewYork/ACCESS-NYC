<?php

/**
 * Plugin Name: NYCO WordPress Config
 * Description: Composer Managed. This "Must Use" WordPress Plugin sets environment variables for a WordPress installation through a YAML configuration file and autoloads environment specific scripts.
 * Author: NYC Opportunity
 */

if (file_exists(WPMU_PLUGIN_DIR . '/wp-config/Config.php'))
  require WPMU_PLUGIN_DIR . '/wp-config/Config.php';