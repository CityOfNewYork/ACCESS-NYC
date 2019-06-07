<?php

namespace BulkWP\BulkDelete\Core\Posts;

use BulkWP\BulkDelete\Core\Base\BaseDeletePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Posts Page.
 *
 * Shows the list of modules that allows you to delete posts.
 *
 * @since 6.0.0
 */
class DeletePostsPage extends BaseDeletePage {
	/**
	 * Position in which the Bulk WP menu should appear.
	 */
	const MENU_POSITION = '26';

	/**
	 * Initialize and setup variables.
	 */
	protected function initialize() {
		$this->page_slug = 'bulk-delete-posts';
		$this->item_type = 'posts';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Posts', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Posts', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message' => __( 'WARNING: Posts deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);

		$this->show_link_in_plugin_list = true;
	}

	public function register() {
		add_menu_page(
			__( 'Bulk WP', 'bulk-delete' ),
			__( 'Bulk WP', 'bulk-delete' ),
			$this->capability,
			$this->page_slug,
			array( $this, 'render_page' ),
			'dashicons-trash',
			$this->get_bulkwp_menu_position()
		);

		parent::register();
	}

	/**
	 * Get the Menu position of BulkWP menu.
	 *
	 * @return int Menu position.
	 */
	protected function get_bulkwp_menu_position() {
		/**
		 * Bulk WP Menu position.
		 *
		 * @since 6.0.0
		 *
		 * @param int Menu Position.
		 */
		return apply_filters( 'bd_bulkwp_menu_position', self::MENU_POSITION );
	}

	/**
	 * Add Help tabs.
	 *
	 * @param array $help_tabs Help tabs.
	 *
	 * @return array Modified list of tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		$overview_tab = array(
			'title'    => __( 'Overview', 'bulk-delete' ),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete posts or schedule them for deletion.', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);

		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}
