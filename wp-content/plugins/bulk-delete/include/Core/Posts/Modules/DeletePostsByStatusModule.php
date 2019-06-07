<?php

namespace BulkWP\BulkDelete\Core\Posts\Modules;

use BulkWP\BulkDelete\Core\Posts\PostsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Posts by Status Module.
 *
 * @since 6.0.0
 */
class DeletePostsByStatusModule extends PostsModule {
	protected function initialize() {
		$this->item_type     = 'posts';
		$this->field_slug    = 'post_status';
		$this->meta_box_slug = 'bd_posts_by_status';
		$this->action        = 'delete_posts_by_status';
		$this->cron_hook     = 'do-bulk-delete-post-status';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-posts-by-status/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-sps';
		$this->messages      = array(
			'box_label'         => __( 'By Post Status', 'bulk-delete' ),
			'scheduled'         => __( 'The selected posts are scheduled for deletion', 'bulk-delete' ),
			'cron_label'        => __( 'Delete Post By Status', 'bulk-delete' ),
			'validation_error'  => __( 'Please select at least one post status from which posts should be deleted', 'bulk-delete' ),
			'confirm_deletion'  => __( 'Are you sure you want to delete all the posts from the selected post status?', 'bulk-delete' ),
			'confirm_scheduled' => __( 'Are you sure you want to schedule deletion of all the posts from the selected post status?', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_one'       => __( 'Deleted %d post from the selected post status', 'bulk-delete' ),
			/* translators: 1 Number of posts deleted */
			'deleted_multiple'  => __( 'Deleted %d posts from the selected post status', 'bulk-delete' ),
		);
	}

	public function render() {
		?>
		<h4><?php _e( 'Select the post statuses from which you want to delete posts', 'bulk-delete' ); ?></h4>

		<fieldset class="options">

			<table class="optiontable">
				<?php $this->render_post_status(); ?>
			</table>

			<table class="optiontable">
				<?php
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

	protected function convert_user_input_to_options( $request, $options ) {
		$options['post_status'] = array_map( 'sanitize_text_field', bd_array_get( $request, 'smbd_' . $this->field_slug, array() ) );

		return $options;
	}

	protected function build_query( $options ) {
		if ( empty( $options['post_status'] ) ) {
			return array();
		}

		$query = array(
			'post_status' => $options['post_status'],
		);

		return $query;
	}
}
