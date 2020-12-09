<?php
/**
 * Plugin Name:   WPScan
 * Plugin URI:    http://wordpress.org/plugins/wpscan/
 * Description:   WPScan WordPress Security Scanner. Scans your system for security vulnerabilities listed in the WPScan Vulnerability Database.
 * Version:       1.13.2
 * Author:        WPScan Team
 * Author URI:    https://wpscan.com/
 * License:       GPLv3
 * License URI:   https://www.gnu.org/licenses/gpl.html
 * Text Domain:   wpscan
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Config.
define( 'WPSCAN_API_URL', 'https://wpscan.com/api/v3' );
define( 'WPSCAN_SIGN_UP_URL', 'https://wpscan.com/register' );
define( 'WPSCAN_STATUS_URL', 'https://status.wpscan.com/' );
define( 'WPSCAN_PROFILE_URL', 'https://wpscan.com/profile' );
define( 'WPSCAN_PLUGIN_FILE', __FILE__ );

// Composer autoload.
require plugin_dir_path( WPSCAN_PLUGIN_FILE ) . 'vendor/autoload.php';

// Start the plugin.
$wpscan = new WPScan\Plugin();

// Activating.
register_activation_hook( WPSCAN_PLUGIN_FILE, array( $wpscan, 'activate' ) );

// Deactivating.
register_deactivation_hook( WPSCAN_PLUGIN_FILE, array( $wpscan, 'deactivate' ) );
