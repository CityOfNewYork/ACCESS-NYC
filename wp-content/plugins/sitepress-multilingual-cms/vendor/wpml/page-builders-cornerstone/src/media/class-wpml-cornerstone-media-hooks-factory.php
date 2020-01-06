<?php

class WPML_Cornerstone_Media_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_Frontend_Action_Loader {
	public function create() {
		return new WPML_Page_Builders_Media_Hooks(
			new WPML_Cornerstone_Update_Media_Factory(),
			WPML_Cornerstone_Integration_Factory::SLUG
		);
	}
}
