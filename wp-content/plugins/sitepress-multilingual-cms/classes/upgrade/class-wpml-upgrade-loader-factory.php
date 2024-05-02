<?php

class WPML_Upgrade_Loader_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress;

		return new WPML_Upgrade_Loader(
			$sitepress,
			wpml_get_upgrade_schema(),
			wpml_load_settings_helper(),
			wpml_get_admin_notices(),
			wpml_get_upgrade_command_factory()
		);
	}
}
