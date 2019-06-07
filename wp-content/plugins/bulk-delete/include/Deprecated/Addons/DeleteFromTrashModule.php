<?php

namespace BulkWP\BulkDelete\Deprecated\Addons;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Adds backward compatibility for Bulk Delete From Trash add-on v0.3 or below.
 *
 * This module will eventually be removed once the add-on is updated.
 *
 * @since 6.0.0
 */
class DeleteFromTrashModule extends DeprecatedModule {
	protected function initialize() {
		$this->addon_class_name = 'Bulk_Delete_From_Trash';
		$this->addon_slug       = 'bulk-delete-from-trash';

		$this->item_type     = 'posts';
		$this->field_slug    = 'trash';
		$this->meta_box_slug = 'bd_posts_from_trash';
		$this->action        = 'delete_pages_from_trash';
		$this->cron_hook     = '';
		$this->scheduler_url = '';
		$this->messages      = array(
			'box_label'  => __( 'Delete Posts from Trash', 'bulk-delete' ),
			'scheduled'  => '',
			'cron_label' => '',
		);
	}

	/**
	 * Set the item type of the module.
	 * The item type determines in which page the module will be displayed.
	 *
	 * @param string $item_type Item type. Possible vales are posts or pages.
	 */
	public function set_item_type( $item_type ) {
		$this->item_type     = $item_type;
		$this->action        = "delete_{$item_type}_from_trash";
		$this->meta_box_slug = "bd_{$item_type}_from_trash";

		if ( 'pages' === $item_type ) {
			$this->messages['box_label'] = __( 'Delete Pages from Trash', 'bulk-delete' );
		}
	}

	/**
	 * Call the appropriate action to render the add-on.
	 */
	public function render() {
		if ( 'posts' === $this->item_type ) {
			/**
			 * Render delete posts from trash box.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_render_delete_posts_from_trash' );
		} elseif ( 'pages' === $this->item_type ) {
			/**
			 * Render delete pages from trash box.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_render_delete_pages_from_trash' );
		}
	}
}
