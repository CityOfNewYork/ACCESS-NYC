<?php

class WPML_ST_Taxonomy_Labels_Translation_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	const BUILD_AJAX_ACTION = 'wpml_get_terms_and_labels_for_taxonomy_table';
	const SAVE_AJAX_ACTION  = 'wpml_tt_save_labels_translation';

	public function create() {
		global $sitepress;

		if ( $this->is_building_or_saving_taxonomy_table() ) {
			$active_languages = $sitepress->get_active_languages( true );
			return new WPML_ST_Taxonomy_Labels_Translation( wpml_st_load_string_factory(), $active_languages );
		}

		return null;
	}

	private function is_building_or_saving_taxonomy_table() {
		return isset( $_POST['action'] )
		       && in_array( $_POST['action'], array( self::BUILD_AJAX_ACTION, self::SAVE_AJAX_ACTION ), true );
	}
}