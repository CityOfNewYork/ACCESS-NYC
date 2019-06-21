<?php
use BulkWP\BulkDelete\Core\Users\Modules\DeleteUsersByUserRoleModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Deprecated Class.
 *
 * It is still here for compatibility reasons and will be removed eventually.
 *
 * Currently used by Bulk Delete Scheduler for Deleting Users by Role add-on - v0.6
 *
 * @author     Sudar
 *
 * @package    BulkDelete\Deprecated
 */
class Bulk_Delete_Users {
	/**
	 * Wire up proper class for backward compatibility.
	 *
	 * @since 5.5
	 *
	 * @param array $delete_options Delete options.
	 *
	 * @return int
	 */
	public static function delete_users_by_role( $delete_options ) {
		$module = new DeleteUsersByUserRoleModule();

		return $module->delete( $delete_options );
	}
}
