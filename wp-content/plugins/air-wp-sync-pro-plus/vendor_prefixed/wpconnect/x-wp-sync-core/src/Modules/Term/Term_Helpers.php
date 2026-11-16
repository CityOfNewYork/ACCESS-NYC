<?php
/**
 * Term module helper functions.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Term;

/**
 * Term Helper functions
 */
class Term_Helpers {

	/**
	 * Get available taxonomies.
	 *
	 * @return \WP_Taxonomy[]
	 */
	public static function get_taxonomies() {
		$taxonomies = get_taxonomies(
			array( 'public' => true ),
			'objects'
		);
		return $taxonomies;
	}
}
