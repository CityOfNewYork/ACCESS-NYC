<?php

namespace BulkWP\BulkDelete\Core\Terms;

use BulkWP\BulkDelete\Core\Base\BaseDeletePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Terms Page.
 *
 * Shows the list of modules that allows you to delete terms.
 *
 * @since 6.0.0
 */
class DeleteTermsPage extends BaseDeletePage {
	protected function initialize() {
		$this->page_slug = 'bulk-delete-terms';
		$this->item_type = 'terms';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Taxonomy Terms', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Terms', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message' => __( 'WARNING: Once deleted, terms cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);

		$this->show_link_in_plugin_list = true;
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
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete terms from taxonomies', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);

		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}
