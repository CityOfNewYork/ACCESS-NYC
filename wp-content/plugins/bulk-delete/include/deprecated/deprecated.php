<?php
/**
 * Deprecated and backward compatibility code.
 * Don't depend on the code in this file. It would be removed in future versions of the plugin.
 *
 * @author     Sudar
 *
 * @package    BulkDelete\Util\Deprecated
 *
 * @since 5.5
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Backward compatibility for delete options.
 *
 * @since 5.5
 *
 * @param array $options Old options.
 *
 * @return array New options.
 */
function bd_delete_options_compatibility( $options ) {
	// Convert bool keys to boolean
	$bool_keys = array( 'restrict', 'force_delete', 'private' );
	foreach ( $bool_keys as $key ) {
		if ( array_key_exists( $key, $options ) ) {
			$options[ $key ] = bd_to_bool( $options[ $key ] );
		}
	}

	// convert old date comparison operators
	if ( array_key_exists( 'date_op', $options ) && array_key_exists( 'days', $options ) ) {
		if ( '<' == $options['date_op'] ) {
			$options['date_op'] = 'before';
		} elseif ( '>' == $options['date_op'] ) {
			$options['date_op'] = 'after';
		}
	}

	return $options;
}
add_filter( 'bd_delete_options', 'bd_delete_options_compatibility' );

/**
 * Handle backward compatibility for Delete Posts by status delete options.
 *
 * Backward compatibility code. Will be removed in Bulk Delete v6.0.
 *
 * @since 5.6.0
 *
 * @param array $delete_options Delete Options.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_post_by_status( $delete_options ) {
	// Format changed in 5.5.0.
	if ( array_key_exists( 'post_status_op', $delete_options ) ) {
		$delete_options['date_op'] = $delete_options['post_status_op'];
		$delete_options['days']    = $delete_options['post_status_days'];
	}

	// Format changed in 5.6.0.
	if ( isset( $delete_options['sticky'] ) ) {
		if ( 'sticky' === $delete_options['sticky'] ) {
			$delete_options['delete-sticky-posts'] = true;
		} else {
			$delete_options['delete-sticky-posts'] = false;
		}
	}

	if ( ! isset( $delete_options['post_status'] ) ) {
		$delete_options['post_status'] = array();
	}

	$old_statuses = array( 'publish', 'draft', 'pending', 'future', 'private' );

	foreach ( $old_statuses as $old_status ) {
		if ( isset( $delete_options[ $old_status ] ) ) {
			$delete_options['post_status'][] = $old_status;
		}
	}

	if ( isset( $delete_options['drafts'] ) && 'drafts' === $delete_options['drafts'] ) {
		$delete_options['post_status'][] = 'draft';
	}

	return $delete_options;
}

/**
 * Enable cron for old pro addons that required separate JavaScript.
 * This will be removed in v6.0.
 *
 * @since 5.5
 *
 * @param array $js_array JavaScript Array
 *
 * @return array Modified JavaScript Array
 */
function bd_enable_cron_for_old_addons( $js_array ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( 'bulk-delete-scheduler-for-deleting-users-by-role/bulk-delete-scheduler-for-deleting-users-by-role.php' ) ) {
		$js_array['pro_iterators'][] = 'u_role';
	}

	return $js_array;
}
add_filter( 'bd_javascript_array', 'bd_enable_cron_for_old_addons' );

// Deprecated functions.

if ( ! function_exists( 'array_get' ) ) {
	/**
	 * Deprecated. Use `bd_array_get`.
	 *
	 * @param mixed      $array
	 * @param mixed      $key
	 * @param mixed|null $default
	 */
	function array_get( $array, $key, $default = null ) {
		return bd_array_get( $array, $key, $default );
	}
}

if ( ! function_exists( 'array_get_bool' ) ) {
	/**
	 * Deprecated. Use `bd_array_get_bool`.
	 *
	 * @param mixed $array
	 * @param mixed $key
	 * @param mixed $default
	 */
	function array_get_bool( $array, $key, $default = false ) {
		return bd_array_get_bool( $array, $key, $default );
	}
}
