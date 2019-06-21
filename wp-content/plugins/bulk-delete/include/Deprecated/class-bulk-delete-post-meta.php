<?php

use BulkWP\BulkDelete\Core\Metas\Modules\DeletePostMetaModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Utility class for deleting Post Meta.
 *
 * All the methods from this class has been migrated to `DeletePostMetaModule` class.
 * This class is still present for backward compatibility purpose, since some of the old add-ons still depend on this class.
 *
 * @since 5.4
 * @since 6.0.0 Deprecated.
 */
class Bulk_Delete_Post_Meta {
	/**
	 * Cron Hook.
	 *
	 * @since 5.4
	 */
	const CRON_HOOK = 'do-bulk-delete-post-meta';

	/**
	 * Delete Post Meta.
	 *
	 * @static
	 *
	 * @since  5.4
	 * @since  6.0.0 Deprecated.
	 *
	 * @param array $delete_options Options for deleting.
	 *
	 * @return int Number of posts that were deleted
	 */
	public static function delete_post_meta( $delete_options ) {
		$module = new DeletePostMetaModule();

		return $module->delete( $delete_options );
	}
}
