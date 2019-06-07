<?php

namespace BulkWP\BulkDelete\Core\Pages\Modules;

use BulkWP\BulkDelete\Core\Pages\PagesModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Pages by Status Module.
 *
 * @since 6.0.0
 */
class DeletePagesByStatusModule extends PagesModule {
	protected function initialize() {
		$this->item_type     = 'pages';
		$this->field_slug    = 'page_status';
		$this->meta_box_slug = 'bd_pages_by_status';
		$this->action        = 'delete_pages_by_status';
		$this->cron_hook     = 'do-bulk-delete-pages-by-status';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-pages-by-status/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-sp';
		$this->messages      = array(
			'box_label'  => __( 'By Page Status', 'bulk-delete' ),
			'scheduled'  => __( 'The selected pages are scheduled for deletion', 'bulk-delete' ),
			'cron_label' => __( 'Delete Pages By status', 'bulk-delete' ),
		);
	}

	public function render() {
		?>
		<!-- Pages start-->
		<h4><?php _e( 'Select the post statuses from which you want to delete pages', 'bulk-delete' ); ?></h4>

		<fieldset class="options">
			<table class="optiontable">
				<?php $this->render_post_status( 'page' ); ?>
			</table>

			<table class="optiontable">
				<?php
				$this->render_filtering_table_header();
				$this->render_restrict_settings();
				$this->render_delete_settings();
				$this->render_limit_settings();
				$this->render_cron_settings();
				?>
			</table>
		</fieldset>

		<?php
		$this->render_submit_button();
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	protected function append_to_js_array( $js_array ) {
		$js_array['error_msg'][ $this->action ]      = 'selectPagePostStatus';
		$js_array['pre_action_msg'][ $this->action ] = 'pagePostStatusWarning';

		$js_array['msg']['selectPagePostStatus']  = __( 'Please select at least one post status from which pages should be deleted', 'bulk-delete' );
		$js_array['msg']['pagePostStatusWarning'] = __( 'Are you sure you want to delete all the pages from the selected post status?', 'bulk-delete' );

		return $js_array;
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['post_status'] = array_map( 'sanitize_text_field', bd_array_get( $request, 'smbd_page_status', array() ) );

		return $options;
	}

	protected function build_query( $options ) {
		if ( empty( $options['post_status'] ) ) {
			return array();
		}

		$query = array(
			'post_type'   => 'page',
			'post_status' => $options['post_status'],
		);

		return $query;
	}

	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of pages deleted */
		return _n( 'Deleted %d page from the selected post status', 'Deleted %d pages from the selected post status', $items_deleted, 'bulk-delete' );
	}
}
