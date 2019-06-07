<?php

namespace BulkWP\BulkDelete\Deprecated\Addons;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Adds backward compatibility for Bulk Delete Posts By Content add-on v1.0.0 or below.
 *
 * This module will eventually be removed once the add-on is updated.
 *
 * @since 6.0.0
 */
class DeletePostsByContentModule extends DeprecatedModule {
	protected function initialize() {
		$this->addon_class_name = 'Bulk_Delete_Posts_By_Content';
		$this->addon_slug       = 'bulk-delete-posts-by-content';

		$this->item_type     = 'posts';
		$this->field_slug    = '';
		$this->meta_box_slug = 'bd-posts-by-content';
		$this->action        = 'bd_delete_posts_by_content';
		$this->cron_hook     = '';
		$this->scheduler_url = '';
		$this->messages      = array(
			'box_label'  => __( 'Delete Posts By Content', 'bulk-delete' ),
			'scheduled'  => '',
			'cron_label' => '',
		);
	}

	/**
	 * Call the render method of the add-on.
	 */
	public function render() {
		$module = new \Bulk_Delete_Posts_By_Content();
		$module->render_meta_box();
	}
}
