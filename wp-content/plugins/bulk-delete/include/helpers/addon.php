<?php
/**
 * Add-on helper functions.
 *
 * These functions are not using namespace since they may be used from a PHP 5.2 file.
 *
 * @since 6.0.0
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Load an Bulk Delete add-on.
 *
 * @since 6.0.0
 *
 * @param string $addon_class   Add-on class name.
 * @param array  $addon_details Add-on Details.
 *
 * @return \BulkWP\BulkDelete\Addon\BaseAddon Instance of the add-on.
 */
function load_bulk_delete_addon( $addon_class, $addon_details ) {
	$addon_info = new \BulkWP\BulkDelete\Core\Addon\AddonInfo( $addon_details );

	$bulk_delete = \BulkWP\BulkDelete\Core\BulkDelete::get_instance();
	$bulk_delete->register_addon_namespace( $addon_info );

	$addon = new $addon_class( $addon_info );
	add_action( 'bd_loaded', array( $addon, 'register' ) );

	return $addon;
}
