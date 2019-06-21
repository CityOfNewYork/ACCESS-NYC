<?php

use BulkWP\BulkDelete\Core\Pages\Modules\DeletePagesByStatusModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Utility class for deleting pages.
 *
 * All the methods from this class has been migrated to individual metabox module classes.
 * This class is still present for backward compatibility purpose, since some of the old add-ons still depend on this class.
 *
 * @since 6.0.0 Deprecated.
 */
class Bulk_Delete_Pages {
	/**
	 * Delete Pages by post status - drafts, pending posts, scheduled posts etc.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_pages_by_status( $delete_options ) {
		$metabox = new DeletePagesByStatusModule();

		return $metabox->delete( $delete_options );
	}
}
