<?php

// phpcs:disable
/**
 * Plugin Name: NYCO WordPress Assets
 * Description: A developer plugin with helpers for managing assets in WordPress. It can be used to enqueue stylesheets with hashed names as well as configure integrations such as Google Analytics, Rollbar, etc.
 * Author: NYC Opportunity
 */
// phpcs:enable

require_once plugin_dir_path(__FILE__) . '/wp-assets/WpAssets.php';
require_once plugin_dir_path(__FILE__) . '/wp-assets/query-monitor/WpAssetsAddOn.php';

/**
 * Set instance of WpAssets to the global scope
 */

$GLOBALS['wp_assets'] = new NYCO\WpAssets();

/**
 * Initialize Query Monitor Add On
 */

new NYCO\QueryMonitor\WpAssetsAddOn($GLOBALS['wp_assets']);
