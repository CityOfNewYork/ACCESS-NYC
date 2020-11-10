<?php

// phpcs:disable
/**
 * Plugin Name: Inline Scripts
 * Description: Helper functions for enqueing scripts and styles. Useful for theme scripts and styles as well as integrations such as Google Analytics, Rollbar, etc.
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
 * Initialize Query Monitor Add-on
 */

new NYCO\QueryMonitor\WpAssetsAddOn($GLOBALS['wp_assets']);
