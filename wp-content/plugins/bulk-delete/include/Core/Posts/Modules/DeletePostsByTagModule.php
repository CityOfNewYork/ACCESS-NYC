<?php

namespace BulkWP\BulkDelete\Core\Posts\Modules;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Tag Module.
 *
 * @since 6.0.0
 */
class DeletePostsByTagModule extends PostsModule {
	/**
	 * Base parameters setup.
	 */
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'tags';
		$this->meta_box_slug = 'bd_by_tag';
		$this->action        = 'delete_posts_by_tag';
		$this->cron_hook     = 'do-bulk-delete-tag';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-posts-by-tag/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-st';
		$this->messages      = array(
			'box_label'  => __( 'By Post Tag', 'bulk-delete' ),
			'scheduled'  => __( 'The selected posts are scheduled for deletion', 'bulk-delete' ),
			'cron_label' => __( 'Delete Post By Tag', 'bulk-delete' ),
		);
	}

	/**
	 * Render Delete posts by tag box.
	 */
	public function render() {
		if ( ! $this->are_tags_present() ) : ?>
			<h4>
				<?php _e( 'There are no tags present in this WordPress installation.', 'bulk-delete' ); ?>
			</h4>
			<?php return; ?>
		<?php endif; ?>

		<h4><?php _e( 'Select the tags from which you want to delete posts', 'bulk-delete' ); ?></h4>

		<!-- Tags start-->
		<fieldset class="options">
			<table class="optiontable">
				<tr>
					<td scope="row" colspan="2">
						<?php $this->render_tags_dropdown(); ?>
					</td>
				</tr>
			</table>

			<table class="optiontable">
				<?php
				$this->render_filtering_table_header();
				$this->render_restrict_settings();
				$this->render_exclude_sticky_settings();
				$this->render_delete_settings();
				$this->render_private_post_settings();
				$this->render_limit_settings();
				$this->render_cron_settings();
				?>
			</table>
		</fieldset>
<?php
		$this->render_submit_button();
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validateSelect2';
		$js_array['error_msg'][ $this->action ]  = 'selectTag';
		$js_array['msg']['selectTag']            = __( 'Please select at least one tag', 'bulk-delete' );

		return $js_array;
	}

	/**
	 * Process delete posts user inputs by tag.
	 *
	 * @param array $request Request array.
	 * @param array $options Options for deleting posts.
	 *
	 * @return array $options  Inputs from user for posts that were need to delete
	 */
	protected function convert_user_input_to_options( $request, $options ) {
		$options['selected_tags'] = bd_array_get( $request, 'smbd_tags', array() );
		$options['private']       = bd_array_get( $request, 'smbd_tags_private', false );

		return $options;
	}

	protected function build_query( $options ) {
		$query = array();

		if ( in_array( 'all', $options['selected_tags'], true ) ) {
			$query['tag__not__in'] = array( 0 );
		} else {
			$query['tag__in'] = $options['selected_tags'];
		}

		return $query;
	}

	/**
	 * Response message for deleting posts.
	 *
	 * @param int $items_deleted count of items deleted.
	 *
	 * @return string Response message
	 */
	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of posts deleted */
		return _n( 'Deleted %d post with the selected post tag', 'Deleted %d posts with the selected post tag', $items_deleted, 'bulk-delete' );
	}
}
