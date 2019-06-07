<?php

use BulkWP\BulkDelete\Core\Users\Modules\DeleteUsersByUserMetaModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Adds backward compatibility for Scheduler for Deleting Users By User Meta add-on v1.0 or below.
 *
 * This class will eventually be removed once the add-on is updated.
 *
 * @since 6.0.0
 */
class Bulk_Delete_Users_By_User_Meta {
	private static $module;

	public static function factory() {
		self::$module = new DeleteUsersByUserMetaModule();

		return self::$module;
	}
}
