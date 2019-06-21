<?php

class WPML_REST_Posts_Hooks_Factory implements IWPML_Deferred_Action_Loader, IWPML_Backend_Action_Loader, IWPML_REST_Action_Loader {

	public function get_load_action() {
		if ( wpml_is_rest_request() ) {
			return WPML_REST_Factory_Loader::REST_API_INIT_ACTION;
		}

		return 'plugins_loaded';
	}

	public function create() {
		global $sitepress, $wpml_term_translations;

		return new WPML_REST_Posts_Hooks( $sitepress, $wpml_term_translations );
	}
}
