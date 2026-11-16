<?php
/**
 * User module helper functions.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User;

/**
 * Post Helper functions
 */
class User_Helpers {

	/**
	 * Get available user roles keys.
	 *
	 * @return string[]
	 */
	public static function get_available_user_roles_keys() {
		$roles = wp_roles();

		if ( ! $roles || ! $roles->get_names() ) {
			return array();
		}

		return array_keys( $roles->get_names() );
	}
}
