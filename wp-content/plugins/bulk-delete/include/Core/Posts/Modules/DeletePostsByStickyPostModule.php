<?php

namespace BulkWP\BulkDelete\Core\Posts\Modules;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Sticky Post.
 *
 * @since 6.0.0
 */
class DeletePostsByStickyPostModule extends PostsModule {
	/**
	 * Did the user requested for unsticking posts instead of deleting them?
	 *
	 * @var bool
	 */
	protected $did_unsticky_post_instead_of_delete = false;

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'sticky_post';
		$this->meta_box_slug = 'delete_posts_by_sticky_post';
		$this->action        = 'delete_posts_by_sticky_post';
		$this->messages      = array(
			'box_label' => __( 'By Sticky Post', 'bulk-delete' ),
		);
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public function render() {
		if ( ! $this->are_sticky_posts_present() ) : ?>
			<h4>
				<?php _e( 'There are no sticky post present in this WordPress installation.', 'bulk-delete' ); ?>
			</h4>
			<?php return; ?>
		<?php endif; // phpcs:ignore?>

		<h4><?php _e( 'Select the sticky post that you want to delete', 'bulk-delete' ); ?></h4>

		<fieldset class="options">
			<table class="optiontable">
				<tr>
					<td scope="row" colspan="2">
						<?php $this->render_sticky_posts_dropdown(); ?>
					</td>
				</tr>
			</table>

			<table class="optiontable">
				<?php
				$this->render_filtering_table_header();
				$this->render_sticky_action_settings();
				$this->render_delete_settings();
				?>
			</table>
		</fieldset>

		<?php
		$this->render_submit_button();
	}

	public function filter_js_array( $js_array ) {
		$js_array['msg']['unstickyPostsWarning'] = __( 'Are you sure you want to remove the selected posts from being sticky?', 'bulk-delete' );
		$js_array['msg']['deletePostsWarning']   = __( 'Are you sure you want to delete all the selected posts?', 'bulk-delete' );
		$js_array['msg']['selectStickyPost']     = __( 'Select at least one sticky post', 'bulk-delete' );

		$js_array['validators'][ $this->action ] = 'validateStickyPost';
		$js_array['error_msg'][ $this->action ]  = 'selectStickyPost';

		$js_array['pre_action_msg'][ $this->action ] = 'DeletePostsByStickyPostPreAction';

		return $js_array;
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function convert_user_input_to_options( $request, $options ) {
		$options['selected_posts'] = bd_array_get( $request, 'smbd_' . $this->field_slug );
		$options['sticky_action']  = bd_array_get( $request, 'smbd_' . $this->field_slug . '_sticky_action' );

		return $options;
	}

	/**
	 * Override the `do_delete` function to handle unsticking posts.
	 *
	 * @inheritdoc
	 *
	 * @param array $options Array of Delete options.
	 *
	 * @return int Number of posts deleted or unsticked.
	 */
	protected function do_delete( $options ) {
		if ( 'unsticky' === $options['sticky_action'] ) {
			$posts_unsticked = 0;

			if ( in_array( 'all', $options['selected_posts'], true ) ) {
				$options['selected_posts'] = get_option( 'sticky_posts' );
			}

			foreach ( $options['selected_posts'] as $post_id ) {
				unstick_post( $post_id );
				$posts_unsticked ++;
			}

			$this->did_unsticky_post_instead_of_delete = true;

			return $posts_unsticked;
		}

		if ( 'delete' === $options['sticky_action'] ) {
			$query = $this->build_query( $options );

			if ( empty( $query ) ) {
				// Short circuit deletion, if nothing needs to be deleted.
				return 0;
			}

			return $this->delete_posts_from_query( $query, $options );
		}

		return 0;
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function build_query( $options ) {
		$query = array();

		if ( in_array( 'all', $options['selected_posts'], true ) ) {
			$query['post__in'] = get_option( 'sticky_posts' );
		} else {
			$query['post__in'] = $options['selected_posts'];
		}

		return $query;
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function get_success_message( $items_deleted ) {
		if ( $this->did_unsticky_post_instead_of_delete ) {
			/* translators: 1 Number of posts unsticked */
			return _n( '%d sticky post was made into normal post', '%d sticky posts were made into normal posts', $items_deleted, 'bulk-delete' );
		}

		/* translators: 1 Number of posts deleted */
		return _n( 'Deleted %d sticky post', 'Deleted %d sticky posts', $items_deleted, 'bulk-delete' );
	}
}
