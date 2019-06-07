<?php

namespace BulkWP\BulkDelete\Deprecated\Addons;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Adds backward compatibility for Bulk Delete Posts By Title add-on v1.0 or below.
 *
 * This module will eventually be removed once the add-on is updated.
 *
 * @since 6.0.0
 */
class DeletePostsByTitleModule extends DeprecatedModule {
	protected function initialize() {
		$this->addon_class_name = 'Bulk_Delete_Posts_By_Title';
		$this->addon_slug       = 'bulk-delete-posts-by-title';

		$this->item_type     = 'posts';
		$this->field_slug    = '';
		$this->meta_box_slug = 'bd_by_title';
		$this->action        = 'delete_posts_by_title';
		$this->cron_hook     = '';
		$this->scheduler_url = '';
		$this->messages      = array(
			'box_label'  => __( 'Delete Posts By Title', 'bulk-delete' ),
			'scheduled'  => '',
			'cron_label' => '',
		);
	}

	/**
	 * Call the render method of the add-on.
	 */
	public function render() {
		\Bulk_Delete_Posts_By_Title::render_delete_posts_by_title_box();
	}
}
