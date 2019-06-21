<?php

use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByCategoryModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByPostTypeModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByStatusModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByTagModule;
use BulkWP\BulkDelete\Core\Posts\Modules\DeletePostsByTaxonomyModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Utility class for deleting posts.
 *
 * All the methods from this class has been migrated to individual metabox module classes.
 * This class is still present for backward compatibility purpose, since some of the old add-ons still depend on this class.
 *
 * @since 6.0.0 Deprecated.
 */
class Bulk_Delete_Posts {
	/**
	 * Delete posts by post status - drafts, pending posts, scheduled posts etc.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_posts_by_status( $delete_options ) {
		$metabox = new DeletePostsByStatusModule();

		return $metabox->delete( $delete_options );
	}

	/**
	 * Delete posts by category.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_posts_by_category( $delete_options ) {
		$metabox = new DeletePostsByCategoryModule();

		return $metabox->delete( $delete_options );
	}

	/**
	 * Delete posts by tag.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_posts_by_tag( $delete_options ) {
		$metabox = new DeletePostsByTagModule();

		return $metabox->delete( $delete_options );
	}

	/**
	 * Delete posts by taxonomy.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_posts_by_taxonomy( $delete_options ) {
		$metabox = new DeletePostsByTaxonomyModule();

		return $metabox->delete( $delete_options );
	}

	/**
	 * Delete posts by post type.
	 *
	 * @since  5.0
	 * @since 6.0.0 Deprecated.
	 * @static
	 *
	 * @param array $delete_options Options for deleting posts.
	 *
	 * @return int $posts_deleted  Number of posts that were deleted
	 */
	public static function delete_posts_by_post_type( $delete_options ) {
		$metabox = new DeletePostsByPostTypeModule();

		return $metabox->delete( $delete_options );
	}
}
