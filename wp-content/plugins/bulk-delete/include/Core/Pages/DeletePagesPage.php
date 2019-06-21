<?php

namespace BulkWP\BulkDelete\Core\Pages;

use BulkWP\BulkDelete\Core\Base\BaseDeletePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Pages Page.
 *
 * Shows the list of modules that allows you to delete pages.
 *
 * @since 6.0.0
 */
class DeletePagesPage extends BaseDeletePage {
	/**
	 * Initialize and setup variables.
	 */
	protected function initialize() {
		$this->page_slug = 'bulk-delete-pages';
		$this->item_type = 'pages';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Pages', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Pages', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message' => __( 'WARNING: Pages deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);
	}

	/**
	 * Add Help tabs.
	 *
	 * @param array $help_tabs Help tabs.
	 *
	 * @return array Modified list of Help tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		$overview_tab = array(
			'title'    => __( 'Overview', 'bulk-delete' ),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete pages or schedule them for deletion.', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);

		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}
