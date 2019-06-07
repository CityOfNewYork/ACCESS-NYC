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
use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Backward compatibility for delete options.
 *
 * @since 5.5
 *
 * @param array                                   $options Old options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module  Modules.
 *
 * @return array New options.
 */
function bd_delete_options_compatibility( $options, $module = null ) {
	if ( $module instanceof BaseModule && 'delete_pages_by_status' === $module->get_action() ) {
		return $options;
	}

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
add_filter( 'bd_delete_options', 'bd_delete_options_compatibility', 10, 2 );

/**
 * Handle backward compatibility for Delete Pages by status delete options.
 *
 * Backward compatibility code. Will eventually be removed.
 *
 * @since 6.0.0
 *
 * @param array                                   $options Delete Options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module  Modules.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_pages( $options, $module = null ) {
	if ( $module instanceof BaseModule && 'delete_pages_by_status' !== $module->get_action() ) {
		return $options;
	}

	if ( array_key_exists( 'page_op', $options ) ) {
		$options['date_op'] = $options['page_op'];
		$options['days']    = $options['page_days'];
	}

	if ( ! array_key_exists( 'post_status', $options ) ) {
		$options['post_status'] = array();
	}

	if ( array_key_exists( 'publish', $options ) && 'published_pages' === $options['publish'] ) {
		$options['post_status'][] = 'publish';
	}

	if ( array_key_exists( 'drafts', $options ) && 'draft_pages' === $options['drafts'] ) {
		$options['post_status'][] = 'draft';
	}

	if ( array_key_exists( 'pending', $options ) && 'pending_pages' === $options['pending'] ) {
		$options['post_status'][] = 'pending';
	}

	if ( array_key_exists( 'future', $options ) && 'future_pages' === $options['future'] ) {
		$options['post_status'][] = 'future';
	}

	if ( array_key_exists( 'private', $options ) && 'private_pages' === $options['private'] ) {
		$options['post_status'][] = 'private';
	}

	return $options;
}
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_pages', 10, 2 );

/**
 * Handle backward compatibility for Delete Posts by category delete options.
 *
 * Backward compatibility code. Will be removed in future Bulk Delete releases.
 *
 * @since 6.0.0
 *
 * @param array $options Delete Options.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_posts_by_category( $options ) {
	if ( array_key_exists( 'cats_op', $options ) ) {
		$options['date_op'] = $options['cats_op'];
		$options['days']    = $options['cats_days'];
	}

	return $options;
}
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_posts_by_category' );

/**
 * Handle backward compatibility for Delete Posts by tag delete options.
 *
 * Backward compatibility code. Will be removed in future Bulk Delete releases.
 *
 * @since 6.0.0
 *
 * @param array                                   $options Delete Options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module  Modules.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_posts_by_tag( $options, $module = null ) {
	if ( $module instanceof BaseModule && 'delete_posts_by_tag' !== $module->get_action() ) {
		return $options;
	}

	if ( array_key_exists( 'tags_op', $options ) ) {
		$options['date_op'] = $options['tags_op'];
		$options['days']    = $options['tags_days'];
	}

	return $options;
}
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_posts_by_tag', 10, 2 );

/**
 * Handle backward compatibility for Delete Posts by status delete options.
 *
 * Backward compatibility code. Will be removed in Bulk Delete v6.0.
 *
 * @since 5.6.0
 * @since 6.0.0 Added Modules parameter.
 *
 * @param array                                   $delete_options Delete Options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module         Modules.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_post_by_status( $delete_options, $module = null ) {
	if ( $module instanceof BaseModule && 'delete_posts_by_status' !== $module->get_action() ) {
		return $delete_options;
	}

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
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_post_by_status', 10, 2 );

/**
 * Handle backward compatibility for Delete Posts by Taxonomy delete options.
 *
 * Backward compatibility code. Will be removed in Bulk Delete v6.0.
 *
 * @since 6.0.0
 *
 * @param array                                   $delete_options Delete Options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module         Modules.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_post_by_taxonomy( $delete_options, $module = null ) {
	if ( $module instanceof BaseModule && 'bd_delete_posts_by_taxonomy' !== $module->get_action() ) {
		return $delete_options;
	}

	if ( array_key_exists( 'taxs_op', $delete_options ) ) {
		$delete_options['date_op'] = $delete_options['taxs_op'];
		$delete_options['days']    = $delete_options['taxs_days'];
	}

	return $delete_options;
}
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_post_by_taxonomy', 10, 2 );

/**
 * Handle backward compatibility for Delete Posts by Post type delete options.
 *
 * Backward compatibility code. Will be removed in Bulk Delete v6.0.
 *
 * @since 6.0.0
 *
 * @param array                                   $delete_options Delete Options.
 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module         Modules.
 *
 * @return array Processed delete options.
 */
function bd_convert_old_options_for_delete_post_by_post_type( $delete_options, $module = null ) {
	if ( $module instanceof BaseModule && 'delete_posts_by_post_type' !== $module->get_action() ) {
		return $delete_options;
	}

	if ( array_key_exists( 'types_op', $delete_options ) ) {
		$delete_options['date_op'] = $delete_options['types_op'];
		$delete_options['days']    = $delete_options['types_days'];
	}

	return $delete_options;
}
add_filter( 'bd_delete_options', 'bd_convert_old_options_for_delete_post_by_post_type', 10, 2 );

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
