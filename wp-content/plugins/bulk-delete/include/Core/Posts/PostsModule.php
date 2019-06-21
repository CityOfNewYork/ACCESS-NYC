<?php
namespace BulkWP\BulkDelete\Core\Posts;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for deleting posts.
 *
 * @since 6.0.0
 */
abstract class PostsModule extends BaseModule {
	/**
	 * Build query params for WP_Query by using delete options.
	 *
	 * Return an empty query array to short-circuit deletion.
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Query.
	 */
	abstract protected function build_query( $options );

	protected $item_type = 'posts';

	/**
	 * Handle common filters.
	 *
	 * @param array $request Request array.
	 *
	 * @return array User options.
	 */
	protected function parse_common_filters( $request ) {
		$options = array();

		$options['restrict']       = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_restrict', false );
		$options['limit_to']       = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_limit_to', 0 ) );
		$options['exclude_sticky'] = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_exclude_sticky', false );
		$options['force_delete']   = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_force_delete', false );

		$options['date_op'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_op' );
		$options['days']    = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_days' ) );

		return $options;
	}

	/**
	 * Helper function to build the query params.
	 *
	 * @param array $options Delete Options.
	 * @param array $query   Params for WP Query.
	 *
	 * @return array Delete options array
	 */
	protected function build_query_options( $options, $query ) {
		return bd_build_query_options( $options, $query );
	}

	/**
	 * Helper function for bd_query which runs query.
	 *
	 * @param array $query Params for WP Query.
	 *
	 * @return array Deleted Post IDs array
	 */
	protected function query( $query ) {
		return bd_query( $query );
	}

	protected function do_delete( $options ) {
		$query = $this->build_query( $options );

		if ( empty( $query ) ) {
			// Short circuit deletion, if nothing needs to be deleted.
			return 0;
		}

		return $this->delete_posts_from_query( $query, $options );
	}

	/**
	 * Build the query using query params and then Delete posts.
	 *
	 * @param array $query   Params for WP Query.
	 * @param array $options Delete Options.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_posts_from_query( $query, $options ) {
		$query = $this->build_query_options( $options, $query );
		$posts = $this->query( $query );

		$post_ids = $this->prepare_posts_for_deletion( $posts, $options );

		/**
		 * Triggered before the posts are deleted.
		 *
		 * @since 6.0.0
		 *
		 * @param array $post_ids List of post ids that are going to be deleted.
		 * @param array $options  List of Delete Options.
		 */
		do_action( 'bd_before_deleting_posts', $post_ids, $options );

		$delete_post_count = $this->delete_posts_by_id( $post_ids, $options['force_delete'] );

		/**
		 * Triggered after the posts are deleted.
		 *
		 * @since 6.0.0
		 *
		 * @param array $options Delete Options.
		 */
		do_action( 'bd_after_deleting_posts', $options );

		return $delete_post_count;
	}

	/**
	 * Render the "private post" setting fields.
	 */
	protected function render_private_post_settings() {
		if ( $this->are_private_posts_present() ) {
			bd_render_private_post_settings( $this->field_slug );
		}
	}

	/**
	 * Delete sticky posts.
	 *
	 * @param bool $force_delete Whether to bypass trash and force deletion.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_sticky_posts( $force_delete ) {
		$sticky_post_ids = get_option( 'sticky_posts' );

		if ( ! is_array( $sticky_post_ids ) ) {
			return 0;
		}

		return $this->delete_posts_by_id( $sticky_post_ids, $force_delete );
	}

	/**
	 * Delete posts by ids.
	 *
	 * @param int[] $post_ids     List of post ids to delete.
	 * @param bool  $force_delete True to force delete posts, False otherwise.
	 *
	 * @return int Number of posts deleted.
	 */
	protected function delete_posts_by_id( $post_ids, $force_delete ) {
		/**
		 * Filter the list of post ids that will be excluded from deletion.
		 *
		 * @since 6.0.0
		 *
		 * @param array $excluded_ids Post IDs to be excluded.
		 */
		$excluded_post_ids = apply_filters( 'bd_excluded_post_ids', array() );

		if ( is_array( $excluded_post_ids ) && ! empty( $excluded_post_ids ) ) {
			$post_ids = array_diff( $post_ids, $excluded_post_ids );
		}

		foreach ( $post_ids as $post_id ) {
			// `$force_delete` parameter to `wp_delete_post` won't work for custom post types.
			// See https://core.trac.wordpress.org/ticket/43672
			if ( $force_delete ) {
				wp_delete_post( $post_id, true );
			} else {
				wp_trash_post( $post_id );
			}
		}

		return count( $post_ids );
	}

	/**
	 * Prepare posts for deletion.
	 *
	 * Individual modules can override this method to exclude posts from getting deleted.
	 *
	 * @since 6.0.2
	 *
	 * @param int[]|\WP_Post[] $posts   List of posts to be deleted. It could be just post_ids.
	 * @param array            $options Delete options.
	 *
	 * @return int[] List of post ids that should be deleted.
	 */
	protected function prepare_posts_for_deletion( array $posts, array $options ) {
		$post_ids = array();

		foreach ( $posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$post_ids[] = $post->ID;
			} else {
				$post_ids[] = $post;
			}
		}

		return $post_ids;
	}
}
