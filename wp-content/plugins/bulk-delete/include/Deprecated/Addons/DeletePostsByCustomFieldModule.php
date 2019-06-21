<?php

namespace BulkWP\BulkDelete\Deprecated\Addons;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Adds backward compatibility for Bulk Delete Posts By Custom Field add-on v1.0 or below.
 *
 * This module will eventually be removed once the add-on is updated.
 *
 * @since 6.0.0
 */
class DeletePostsByCustomFieldModule extends DeprecatedModule {
	protected function initialize() {
		$this->addon_class_name = 'Bulk_Delete_Posts_By_Custom_Field';
		$this->addon_slug       = 'bulk-delete-posts-by-custom-field';

		$this->item_type     = 'posts';
		$this->field_slug    = 'custom-field';
		$this->meta_box_slug = 'bd_by_custom_field';
		$this->action        = 'delete_posts_by_custom_field';
		$this->cron_hook     = '';
		$this->scheduler_url = '';
		$this->messages      = array(
			'box_label'  => __( 'Delete Posts By Custom Field', 'bulk-delete' ),
			'scheduled'  => '',
			'cron_label' => '',
		);
	}

	/**
	 * Call the render method of the add-on.
	 */
	public function render() {
		\Bulk_Delete_Posts_By_Custom_Field::render_delete_posts_by_custom_field_box();
	}
}
