<?php

namespace BulkWP\BulkDelete\Core\Base\Mixin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Container of all Fetch related methods.
 *
 * Ideally this should be a Trait. Since Bulk Delete still supports PHP 5.3, this is implemented as a class.
 * Once the minimum requirement is increased to PHP 5.3, this will be changed into a Trait.
 *
 * @since 6.0.0
 */
abstract class Fetcher {
	/**
	 * Get the list of public post types registered in WordPress.
	 *
	 * @return \WP_Post_Type[]
	 */
	protected function get_post_types() {
		return bd_get_post_types();
	}

	/**
	 * Get the list of post statuses.
	 *
	 * This includes all custom post status, but excludes built-in private posts.
	 *
	 * @return array List of post status objects.
	 */
	protected function get_post_statuses() {
		return bd_get_post_statuses();
	}

	/**
	 * Get the list of post types by post status and count.
	 *
	 * @return array Post types by post status.
	 */
	protected function get_post_types_by_status() {
		$post_types_by_status = array();

		$post_types    = $this->get_post_types();
		$post_statuses = $this->get_post_statuses();

		foreach ( $post_types as $post_type ) {
			$post_type_name = $post_type->name;
			$count_posts    = wp_count_posts( $post_type_name );

			foreach ( $post_statuses as $post_status ) {
				$post_status_name = $post_status->name;

				if ( ! property_exists( $count_posts, $post_status_name ) ) {
					continue;
				}

				if ( 0 === $count_posts->{$post_status_name} ) {
					continue;
				}

				$post_type_key               = $post_type->labels->singular_name . ' (' . $post_type_name . ')';
				$post_type_with_status_key   = $post_type_name . '|' . $post_status_name;
				$post_type_with_status_label = $post_status->label . ' (' . $count_posts->{$post_status_name} . ' ' . __( 'Posts', 'bulk-delete' ) . ')';

				$post_types_by_status[ $post_type_key ][ $post_type_with_status_key ] = $post_type_with_status_label;
			}
		}

		return $post_types_by_status;
	}

	/**
	 * Get the list of sticky posts.
	 *
	 * @return array List of sticky posts.
	 */
	protected function get_sticky_posts() {
		$sticky_post_ids = get_option( 'sticky_posts' );

		if ( empty( $sticky_post_ids ) ) {
			return array();
		}

		return get_posts(
			array(
				'numberposts' => count( $sticky_post_ids ),
				'post__in'    => $sticky_post_ids,
			)
		);
	}

	/**
	 * Get the list of categories.
	 *
	 * @return array List of categories.
	 */
	protected function get_categories() {
		$enhanced_select_threshold = $this->get_enhanced_select_threshold();

		$categories = get_categories(
			array(
				'hide_empty' => false,
				'number'     => $enhanced_select_threshold,
			)
		);

		return $categories;
	}

	/**
	 * Are tags present in this WordPress installation?
	 *
	 * Only one tag is retrieved to check if tags are present for performance reasons.
	 *
	 * @return bool True if tags are present, False otherwise.
	 */
	protected function are_tags_present() {
		$tags = $this->get_tags( 1 );

		return ( count( $tags ) > 0 );
	}

	/**
	 * Get the list of tags.
	 *
	 * @param int $max_count The maximum number of tags to be returned (Optional). Default 0.
	 *                       If 0 then the maximum number of tags specified in `get_enhanced_select_threshold` will be returned.
	 *
	 * @return array List of tags.
	 */
	protected function get_tags( $max_count = 0 ) {
		if ( absint( $max_count ) === 0 ) {
			$max_count = $this->get_enhanced_select_threshold();
		}

		$tags = get_tags(
			array(
				'hide_empty' => false,
				'number'     => $max_count,
			)
		);

		return $tags;
	}

	/**
	 * Are sticky post present in this WordPress installation?
	 *
	 * @return bool True if sticky posts are present, False otherwise.
	 */
	protected function are_sticky_posts_present() {
		$sticky_post_ids = get_option( 'sticky_posts' );

		if ( ! is_array( $sticky_post_ids ) ) {
			return false;
		}

		return ( count( $sticky_post_ids ) > 0 );
	}

	/**
	 * Check if private posts are present in a post type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	protected function are_private_posts_present( $post_type = 'any' ) {
		$args  = array(
			'post_status' => array( 'private' ),
			'post_type'   => $post_type,
		);
		$query = new \WP_Query( $args );

		return $query->have_posts();
	}

	/**
	 * Get the number of users present in a role.
	 *
	 * `count_users` function is very expensive. So this function takes an optional parameter to cache it.
	 *
	 * @see count_users
	 *
	 * @param string     $role        Role slug.
	 * @param array|null $users_count Result of the `count_users` function. Default null.
	 *
	 * @return int Number of users in that role.
	 */
	protected function get_user_count_by_role( $role, $users_count = null ) {
		if ( is_null( $users_count ) || ! is_array( $users_count ) ) {
			$users_count = count_users();
		}

		$roles = $users_count['avail_roles'];

		if ( ! array_key_exists( $role, $roles ) ) {
			return 0;
		}

		return $roles[ $role ];
	}

	/**
	 * Get the threshold after which enhanced select should be used.
	 *
	 * @since 6.0.1 moved to Fetcher from Renderer.
	 *
	 * @return int Threshold.
	 */
	protected function get_enhanced_select_threshold() {
		/**
		 * Filter the enhanced select threshold.
		 *
		 * @since 6.0.0
		 *
		 * @param int Threshold.
		 */
		return apply_filters( 'bd_enhanced_select_threshold', 1000 );
	}
}
