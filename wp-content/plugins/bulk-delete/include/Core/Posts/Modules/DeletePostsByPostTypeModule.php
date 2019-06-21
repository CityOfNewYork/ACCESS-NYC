<?php

namespace BulkWP\BulkDelete\Core\Posts\Modules;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Post Type Module.
 *
 * @since 6.0.0
 */
class DeletePostsByPostTypeModule extends PostsModule {
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'types';
		$this->meta_box_slug = 'bd_posts_by_types';
		$this->action        = 'delete_posts_by_post_type';
		$this->cron_hook     = 'do-bulk-delete-post-type';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-posts-by-post-type/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-spt';
		$this->messages      = array(
			'box_label'         => __( 'By Post Type', 'bulk-delete' ),
			'scheduled'         => __( 'Posts from the selected post type and post status are scheduled for deletion', 'bulk-delete' ),
			'cron_label'        => __( 'Delete Post By Post Type', 'bulk-delete' ),
			'confirm_deletion'  => __( 'Are you sure you want to delete all the posts from the selected post type and post status?', 'bulk-delete' ),
			'confirm_scheduled' => __( 'Are you sure you want to schedule the deletion of all the posts from the selected post type and post status?', 'bulk-delete' ),
			'validation_error'  => __( 'Please select at least one post type from which you want to delete posts', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_one'       => __( 'Deleted %d post from the selected post type and post status', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_multiple'  => __( 'Deleted %d posts from the selected post type and post status', 'bulk-delete' ),
		);
	}

	public function render() {
		?>

		<h4>
			<?php _e( 'Select the post type and the status from which you want to delete posts', 'bulk-delete' ); ?>
		</h4>

		<fieldset class="options">
			<table class="optiontable">

				<?php
				$this->render_post_type_with_status();
				$this->render_filtering_table_header();
				$this->render_restrict_settings();
				$this->render_exclude_sticky_settings();
				$this->render_delete_settings();
				$this->render_limit_settings();
				$this->render_cron_settings();
				?>

			</table>
		</fieldset>

		<?php
		$this->render_submit_button();
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validatePostTypeSelect2';

		return $js_array;
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['selected_types'] = bd_array_get( $request, 'smbd_' . $this->field_slug );

		return $options;
	}

	public function delete( $options ) {
		/**
		 * Filter delete options before deleting posts.
		 *
		 * @since 6.0.0 Added `Modules` parameter.
		 *
		 * @param array $options Delete options.
		 * @param \BulkWP\BulkDelete\Core\Base\BaseModule Modules that is triggering deletion.
		 */
		$options = apply_filters( 'bd_delete_options', $options, $this );

		$posts_deleted  = 0;
		$selected_types = $options['selected_types'];

		foreach ( $selected_types as $selected_type ) {
			$query = $this->build_query( $selected_type );

			$posts_deleted += $this->delete_posts_from_query( $query, $options );
		}

		return $posts_deleted;
	}

	/**
	 * Build the query from the selected type.
	 *
	 * In this Module, this function accepts a string and not an array.
	 *
	 * @param string $selected_type Post type.
	 *
	 * @return array Query params.
	 */
	protected function build_query( $selected_type ) {
		$type_status = $this->split_post_type_and_status( $selected_type );

		$type   = $type_status['type'];
		$status = $type_status['status'];

		$query = array(
			'post_status' => $status,
			'post_type'   => $type,
		);

		return $query;
	}
}
