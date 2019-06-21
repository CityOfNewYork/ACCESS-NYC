<?php
/**
 * Load Bulk Delete plugin.
 *
 * We need this load code in a separate file since it requires namespace
 * and using namespace in PHP 5.2 will generate a fatal error.
 *
 * @since 6.0.0
 */
use BulkWP\BulkDelete\BulkDeleteAutoloader;
use BulkWP\BulkDelete\Core\BulkDelete;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Load Bulk Delete plugin.
 *
 * @since 6.0.0
 *
 * @param string $plugin_file Main plugin file.
 */
function bulk_delete_load( $plugin_file ) {
	$plugin_dir = plugin_dir_path( $plugin_file );

	// setup autoloader.
	require_once 'include/BulkDeleteAutoloader.php';

	$loader = new BulkDeleteAutoloader();
	$loader->set_custom_mapping( bd_get_custom_class_map( $plugin_dir ) );

	$loader->add_namespace( 'BulkWP\\BulkDelete\\', $plugin_dir . 'include' );
	$loader->add_namespace( 'Sudar\\WPSystemInfo', $plugin_dir . 'vendor/sudar/wp-system-info/src/' );

	$custom_include_files = bd_get_custom_include_files();
	foreach ( $custom_include_files as $custom_include_file ) {
		$loader->add_file( $plugin_dir . $custom_include_file );
	}

	$loader->register();

	$plugin = BulkDelete::get_instance();
	$plugin->set_plugin_file( $plugin_file );
	$plugin->set_loader( $loader );

	add_action( 'plugins_loaded', array( $plugin, 'load' ), 101 );
}

/**
 * Get class map of legacy classes.
 *
 * These classes don't have namespace and so can't be autoloaded automatically.
 * This function would be eventually removed once all the classes are loaded.
 *
 * @since 6.0.0
 *
 * @param string $plugin_dir Path to plugin directory.
 *
 * @return array Class map.
 */
function bd_get_custom_class_map( $plugin_dir ) {
	return array(
		'BD_Base_Page'                             => $plugin_dir . 'include/base/class-bd-base-page.php',
		'Bulk_Delete_Help_Screen'                  => $plugin_dir . 'include/ui/class-bulk-delete-help-screen.php',
		'BD_License'                               => $plugin_dir . 'include/license/class-bd-license.php',
		'BD_License_Handler'                       => $plugin_dir . 'include/license/class-bd-license-handler.php',
		'BD_EDD_API_Wrapper'                       => $plugin_dir . 'include/license/class-bd-edd-api-wrapper.php',
		'Bulk_Delete_Misc'                         => $plugin_dir . 'include/misc/class-bulk-delete-misc.php',
		'Bulk_Delete_Jetpack_Contact_Form_Message' => $plugin_dir . 'include/misc/class-bulk-delete-jetpack-contact-form-messages.php',
		'BD_Settings_Page'                         => $plugin_dir . 'include/settings/class-bd-settings-page.php',
		'BD_Settings'                              => $plugin_dir . 'include/settings/class-bd-settings.php',

		// Compatibility. Will be removed once compatibility is addressed.
		'BD_Meta_Box_Module'                       => $plugin_dir . 'include/base/class-bd-meta-box-module.php', // Used in Bulk Delete Attachments Addon - v1.2.
		'BD_Page'                                  => $plugin_dir . 'include/base/class-bd-page.php', // Used in Bulk Delete Attachments Addon - v1.2.

		'BD_Addon'                                 => $plugin_dir . 'include/Deprecated/class-bd-addon.php', // Used in Bulk Delete Attachments Addon - v1.2.
		'BD_Base_Addon'                            => $plugin_dir . 'include/Deprecated/class-bd-base-addon.php', // Used in Bulk Delete Attachments Addon - v1.2.
		'BD_Scheduler_Addon'                       => $plugin_dir . 'include/Deprecated/class-bd-scheduler-addon.php', // Used in Scheduler for Deleting Attachments and Users by Meta Addon.
		'Bulk_Delete_Users_By_User_Meta'           => $plugin_dir . 'include/Deprecated/Bulk_Delete_Users_By_User_Meta.php', // Used in Scheduler for Deleting Users by User Meta Addon - v1.0.

		// Deprecated classes.
		'Bulk_Delete_Posts'                        => $plugin_dir . 'include/Deprecated/class-bulk-delete-posts.php',
		'Bulk_Delete_Pages'                        => $plugin_dir . 'include/Deprecated/class-bulk-delete-pages.php',
		'Bulk_Delete_Users'                        => $plugin_dir . 'include/Deprecated/class-bulk-delete-users.php',
		'Bulk_Delete_Post_Meta'                    => $plugin_dir . 'include/Deprecated/class-bulk-delete-post-meta.php',
		'Bulk_Delete_User_Meta'                    => $plugin_dir . 'include/Deprecated/class-bulk-delete-user-meta.php',
	);
}

/**
 * Get the list of custom included files.
 *
 * These files will be autoloaded using the autoloader.
 *
 * @since 6.0.0
 *
 * @return array List of files.
 */
function bd_get_custom_include_files() {
	return array(
		'include/addons/addon-list.php',
		'include/addons/util.php',
		'include/compatibility/simple-login-log.php',
		'include/compatibility/the-event-calendar.php',
		'include/compatibility/woocommerce.php',
		'include/compatibility/advanced-custom-fields-pro.php',
		'include/helpers/common.php',
		'include/helpers/addon.php',
		'include/ui/form.php',
		'include/ui/admin-ui.php',
		'include/util/query.php',
		'include/settings/setting-helpers.php',
		'include/Deprecated/deprecated.php',
		'include/Deprecated/support-old-addons.php',
	);
}
