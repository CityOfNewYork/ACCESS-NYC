<?php

class WPML_Elementor_Translate_IDs_Factory implements IWPML_Frontend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		return new WPML_Elementor_Translate_IDs( new \WPML\Utils\DebugBackTrace() );
	}
}
