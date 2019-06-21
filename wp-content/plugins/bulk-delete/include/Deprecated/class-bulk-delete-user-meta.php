<?php

use BulkWP\BulkDelete\Core\Metas\Modules\DeleteUserMetaModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Utility class for deleting User Meta.
 *
 * All the methods from this class has been migrated to `DeleteUserMetaModule` class.
 * This class is still present for backward compatibility purpose, since some of the old add-ons still depend on this class.
 *
 * @since 5.4
 * @since 6.0.0 Deprecated.
 */
class Bulk_Delete_User_Meta {
	/**
	 * Cron Hook.
	 *
	 * @since 5.4
	 */
	const CRON_HOOK = 'do-bulk-delete-user-meta';

	/**
	 * Delete User Meta.
	 *
	 * @static
	 *
	 * @since  5.4
	 * @since  6.0.0 Deprecated.
	 *
	 * @param array $delete_options Options for deleting.
	 *
	 * @return int Number of users that were deleted
	 */
	public static function delete_user_meta( $delete_options ) {
		$module = new DeleteUserMetaModule();

		return $module->delete( $delete_options );
	}
}
