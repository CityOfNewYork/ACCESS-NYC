<?php
/**
 * Plugin Name: Air WP Sync Pro+ - Airtable to WordPress
 * Plugin URI: https://wpconnect.co/air-wp-sync-plugin/
 * Description: Swiftly sync Airtable to your WordPress website!
 * Version: 2.9.4
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author: WP connect
 * Author URI: https://wpconnect.co/
 * License: GPLv2 or later License
 * Text Domain: air-wp-sync-pro-plus
 * Domain Path: /languages/
 *
 * @package Air_WP_Sync_Pro
 */

namespace Air_WP_Sync_Pro;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\EDD\Licensing;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Models\Licensing_Data;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if Air WP Sync pro is activated.
( function () {
	if ( file_exists( WP_PLUGIN_DIR . '/air-wp-sync-pro/air-wp-sync.php' ) ) {
		if ( is_multisite() && is_network_admin() ) {
			$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins = array_keys( $active_plugins );
		} else {
			$active_plugins = (array) get_option( 'active_plugins', array() );
		}
		if ( in_array( 'air-wp-sync-pro/air-wp-sync.php', $active_plugins, true ) ) {
			set_transient( 'airwpsync_pro_deactivated_notice_id', 1, 1 * HOUR_IN_SECONDS );
			deactivate_plugins( 'air-wp-sync-pro/air-wp-sync.php' );
		}

		if ( (int) get_transient( 'airwpsync_pro_deactivated_notice_id' ) === 1 ) {
			add_action(
				'admin_notices',
				function () {
					?><div class="notice notice-warning">
						<p><?php esc_html_e( "Air WP Sync Pro+ and Air WP Sync Pro should not be active at the same time. We've automatically deactivated Air WP Sync Pro.", 'air-wp-sync-pro-plus' ); ?></p>
					</div>
					<?php
					delete_transient( 'airwpsync_pro_deactivated_notice_id' );
				}
			);
		}
	}
} )();

define( 'AIR_WP_SYNC_PRO_VERSION', '2.9.4' );
define( 'AIR_WP_SYNC_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIR_WP_SYNC_PRO_PLUGIN_FILE', __FILE__ );
define( 'AIR_WP_SYNC_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIR_WP_SYNC_PRO_BASENAME', plugin_basename( __FILE__ ) );
define( 'AIR_WP_SYNC_PRO_LOGDIR', wp_upload_dir( null, false )['basedir'] . '/airwpsync-logs/' );
define( 'AIR_WP_SYNC_PRO_TRANSLATIONS_PACK_URL', 'https://packages.translationspress.com/wp-connect/air-wp-sync-pro-plus/packages.json' );

require_once 'vendor/autoload.php';
require_once AIR_WP_SYNC_PRO_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
require_once AIR_WP_SYNC_PRO_PLUGIN_DIR . 'includes/class-air-wp-sync-bootstrap.php';

( function () {
	$services = Services::get_instance();
	$services->set(
		'plugin_context',
		Plugin_Context::from_array(
			array(
				'plugin_name'                => 'Air WP Sync Pro',
				'admin_menu_name'            => 'Air WP Sync',
				'plugin_vendor_prefixed_url' => trailingslashit( AIR_WP_SYNC_PRO_PLUGIN_URL ) . 'vendor_prefixed',
				'plugin_file'                => AIR_WP_SYNC_PRO_PLUGIN_FILE,
				'plugin_dir'                 => AIR_WP_SYNC_PRO_PLUGIN_DIR,
				'plugin_version'             => AIR_WP_SYNC_PRO_VERSION,
				'plugin_basename'            => AIR_WP_SYNC_PRO_BASENAME,
				'log_dir'                    => AIR_WP_SYNC_PRO_LOGDIR,
				'translations_pack_url'      => AIR_WP_SYNC_PRO_TRANSLATIONS_PACK_URL,
			)
		)
	);
} )();

if ( ! defined( 'AIR_WP_SYNC_PRO_WPC_PRODUCT_ID' ) ) {
	define( 'AIR_WP_SYNC_PRO_WPC_PRODUCT_ID', 7357 );
}

if ( ! defined( 'AIR_WP_SYNC_PRO_WPC_URL' ) ) {
	define( 'AIR_WP_SYNC_PRO_WPC_URL', 'https://wpconnect.co' );
}

register_deactivation_hook( __FILE__, __NAMESPACE__ . '\air_wp_sync_pro_deactivate' );


if ( ! function_exists( __NAMESPACE__ . '\air_wp_sync_pro_deactivate' ) ) {
	/**
	 * The code that runs during plugin deactivation.
	 */
	function air_wp_sync_pro_deactivate() {
		// flush rewrite rules.
		flush_rewrite_rules();
		// Clear hooks.
		foreach ( _get_cron_array() as $cron ) {
			foreach ( array_keys( $cron ) as $hook ) {
				if ( strpos( $hook, 'air_wp_sync_importer_' ) === 0 ) {
					wp_clear_scheduled_hook( $hook );
				}
			}
		}
	}
}

register_uninstall_hook( __FILE__, __NAMESPACE__ . '\air_wp_sync_pro_uninstall' );
if ( ! function_exists( __NAMESPACE__ . '\air_wp_sync_pro_uninstall' ) ) {
	/**
	 * Uninstall procedure
	 */
	function air_wp_sync_pro_uninstall() {
		$options     = new Options_Settings( 'air_wp_sync_options' );
		$licensing   = new Licensing(
			new Licensing_Data(
				AIR_WP_SYNC_PRO_WPC_URL,
				AIR_WP_SYNC_PRO_WPC_PRODUCT_ID,
				'https://wpconnect.co/air-wp-sync-plugin/'
			)
		);
		$license_key = $options->get( 'license_key' );

		if ( ! empty( $license_key ) ) {
			$result = $licensing->deactivate( $license_key );
			if ( ! is_wp_error( $result ) ) {
				$options->delete_options();
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $result->get_error_message() );
			}
		}
	}
}

// Init plugin.
new Air_WP_Sync_Bootstrap();
