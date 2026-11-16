<?php
/**
 * Post Module helpers.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post;

/**
 * Class Post_Helpers
 */
class Post_Helpers {
	/**
	 * Get available post types
	 *
	 * @return array
	 */
	public static function get_post_types() {
		$excluded          = array(
			// WP.
			'attachment',
			// WPC.
			'airwpsync-connection',
			'ntwpsync-connection',
			'ntwpsync-content',
			'tmtwpsync-connection',
			// Others known incompatible post types.
			'wpforms',
			'e-landing-page',
			'et_tb_item',
			'et_code_snippet',
			'et_theme_builder',
			'et_template',
			'et_header_layout',
			'et_body_layout',
			'et_pb_layout',
			'et_footer_layout',
			'fl-builder-template',
			'fl-theme-layout',
			'mc4wp-form',
			'edd_payment',
			'edd_discount',
			'edd_license',
			'edd_license_log',
			'edd_receipt',
			'edd_subscription_log',
			'product_variation',
			'shop_order',
			'shop_order_refund',
			'shop_coupon',
			'shop_order_placehold',
			'nf_sub',
		);
		$excluded_prefixes = array(
			'acf-',
			'elementor_',
			'elementor-',
			'edd_',
			'polylang_',
			'wpcf7_',
		);

		$post_types = array();

		$wp_post_types = get_post_types( null, 'objects' );

		foreach ( $wp_post_types as $wp_post_type ) {
			// Skip excluded post types.
			if ( in_array( $wp_post_type->name, $excluded, true ) ) {
				continue;
			}
			// Skip excluded post types by prefix.
			foreach ( $excluded_prefixes as $excluded_prefix ) {
				if ( strpos( $wp_post_type->name, $excluded_prefix ) === 0 ) {
					continue 2;
				}
			}

			// Skip WP private post types.
			if ( $wp_post_type->_builtin && ! $wp_post_type->public ) {
				continue;
			}

			$post_types[] = array(
				'value'   => $wp_post_type->name,
				'label'   => $wp_post_type->labels->singular_name,
				'enabled' => true,
			);
		}

		$post_types[] = array(
			'value'   => 'custom',
			'label'   => __( 'Create new post type...', 'air-wp-sync-pro-plus' ),
			'enabled' => true,
		);

		/**
		 * Filter available post types.
		 *
		 * @param array $post_types Available post types.
		 */
		return apply_filters( 'airwpsync/get_post_types', $post_types );
	}

	/**
	 * Get available post stati.
	 *
	 * @return array
	 */
	public static function get_post_stati() {
		$post_stati    = array();
		$wp_post_stati = get_post_stati(
			array( 'internal' => false ),
			'objects'
		);

		$excluded_prefixes = array(
			'acf-',
			'wc-',
		);

		foreach ( $wp_post_stati as $wp_post_status ) {
			// Skip excluded post status by prefix.
			foreach ( $excluded_prefixes as $excluded_prefix ) {
				if ( strpos( $wp_post_status->name, $excluded_prefix ) === 0 ) {
					continue 2;
				}
			}
			$post_stati[] = array(
				'value'   => $wp_post_status->name,
				'label'   => $wp_post_status->label,
				'enabled' => true,
			);
		}

		/**
		 * Filters available post stati
		 *
		 * @param array $post_stati Post stati.
		 */
		return apply_filters( 'airwpsync/get_post_stati', $post_stati );
	}

	/**
	 * Get post authors
	 *
	 * @return array
	 */
	public static function get_post_authors() {
		$authors    = array();
		$wp_authors = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ) ) );
		foreach ( $wp_authors as $wp_author ) {
			$authors[] = array(
				'value'   => $wp_author->ID,
				'label'   => $wp_author->display_name,
				'enabled' => true,
			);
		}

		/**
		 * Filters available post authors
		 *
		 * @param array $authors Post authors.
		 */
		return apply_filters( 'airwpsync/get_post_authors', $authors );
	}

	/**
	 * Get attached synced media from post id.
	 *
	 * @param int $post_id The post the media is attached to.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_attached_synced_media_from_post( $post_id ) {
		return get_posts(
			array(
				'post_type'   => 'attachment',
				'post_parent' => $post_id,
				'post_status' => 'inherit',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_air_wp_sync_record_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);
	}
}
