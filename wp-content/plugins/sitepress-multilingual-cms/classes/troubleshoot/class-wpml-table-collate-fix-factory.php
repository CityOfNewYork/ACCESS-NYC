<?php

class WPML_Table_Collate_Fix_Factory implements IWPML_AJAX_Action_Loader, IWPML_Backend_Action_Loader {

	public function create() {
		global $wpdb;

		return new WPML_Table_Collate_Fix( $wpdb, wpml_get_upgrade_schema() );
	}
}