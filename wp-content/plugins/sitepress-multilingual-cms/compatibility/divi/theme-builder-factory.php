<?php

namespace WPML\Compatibility\Divi;

class ThemeBuilderFactory implements \IWPML_Deferred_Action_Loader, \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	public function get_load_action() {
		return 'init';
	}

	public function create() {
		global $sitepress;

		return new ThemeBuilder( $sitepress );
	}
}
