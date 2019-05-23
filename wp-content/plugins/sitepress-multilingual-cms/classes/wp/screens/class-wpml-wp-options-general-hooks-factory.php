<?php

class WPML_WP_Options_General_Hooks_Factory extends WPML_Current_Screen_Loader_Factory {

	protected function get_screen_regex() {
		return '/^options-general$/';
	}

	protected function create_hooks() {
		return new WPML_WP_Options_General_Hooks();
	}
}
