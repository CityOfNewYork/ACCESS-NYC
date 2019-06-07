<?php

namespace BulkWP\BulkDelete\Core\Users;

use BulkWP\BulkDelete\Core\Base\BaseDeletePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Users Page.
 *
 * Shows the list of modules that provides the ability to delete users.
 *
 * @since 5.5
 * @since 6.0.0 Renamed to DeleteUsersPage
 */
class DeleteUsersPage extends BaseDeletePage {
	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 */
	protected function initialize() {
		$this->page_slug = 'bulk-delete-users';
		$this->item_type = 'users';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Users', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Users', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message' => __( 'WARNING: Users deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);

		$this->show_link_in_plugin_list = true;
	}

	/**
	 * Add Help tabs.
	 *
	 * @since 5.5
	 *
	 * @param array $help_tabs List of help tabs.
	 *
	 * @return array Modified list of help tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		$overview_tab = array(
			'title'    => __( 'Overview', 'bulk-delete' ),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete users or schedule them for deletion.', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);

		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}
