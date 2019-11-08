<?php
/**
 * WPML_Attachment_Action_Factory
 *
 * @package WPML
 */

/**
 * Class WPML_Attachment_Action_Factory
 */
class WPML_Attachment_Action_Factory implements
	IWPML_Backend_Action_Loader, IWPML_Frontend_Action_Loader, IWPML_AJAX_Action_Loader, IWPML_Deferred_Action_Loader {

	/**
	 * Get load action.
	 *
	 * @return string
	 */
	public function get_load_action() {
		return 'wpml_loaded';
	}

	/**
	 * Create attachment action.
	 *
	 * @return WPML_Attachment_Action
	 */
	public function create() {
		global $sitepress, $wpdb;

		return new WPML_Attachment_Action( $sitepress, $wpdb );
	}
}
