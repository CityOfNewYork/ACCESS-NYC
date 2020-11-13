<?php

/**
 * Plugin Name:   WPScan
 * Plugin URI:    http://wordpress.org/plugins/wpscan/
 * Description:   WPScan WordPress Security Scanner. Scans your system for security vulnerabilities listed in the WPScan Vulnerability Database.
 * Version:       1.11
 * Author:        WPScan Team
 * Author URI:    https://wpscan.com/
 * License:       GPLv3
 * License URI:   https://www.gnu.org/licenses/gpl.html
 * Text Domain:   wpscan
 */

// Fail Security Check
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// Config
define( 'WPSCAN_API_URL', 'https://wpscan.com/api/v3' );
define( 'WPSCAN_SIGN_UP_URL', 'https://wpscan.com/signin' );
define( 'WPSCAN_PROFILE_URL', 'https://wpscan.com/profile' );
define( 'WPSCAN_PLUGIN_FILE', __FILE__ );

//Includes
require_once 'includes/class-wpscan.php';
require_once 'includes/class-settings.php';
require_once 'includes/class-account.php';
require_once 'includes/class-summary.php';
require_once 'includes/class-notification.php';
require_once 'includes/class-admin-bar.php';
require_once 'includes/class-dashboard.php';
require_once 'includes/class-report.php';
require_once 'includes/class-site-health-integration.php';

// Activating
register_activation_hook( __FILE__, array( 'WPScan', 'activate' ) );

// Deactivating
register_deactivation_hook( __FILE__, array( 'WPScan', 'deactivate' ) );

// Initialize
add_action( 'init', array( 'WPScan', 'init' ) );
add_action( 'wpscan_schedule', 'WPScan::check_now' );