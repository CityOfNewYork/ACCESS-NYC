<?php

class WPML_TM_Upgrade_Loader_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress, $wpdb;

		return new WPML_TM_Upgrade_Loader( $sitepress, wpml_get_upgrade_schema(), wpml_load_settings_helper(), wpml_get_admin_notices(), new WPML_Upgrade_Command_Factory() );
	}
}