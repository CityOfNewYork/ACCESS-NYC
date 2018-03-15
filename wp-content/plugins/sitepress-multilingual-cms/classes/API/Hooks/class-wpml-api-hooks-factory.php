<?php

class WPML_API_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_Frontend_Action_Loader {

	public function create() {
		global $wpdb, $sitepress;

		$hooks   = array();

		$hooks[] = new WPML_API_Hook_Sync_Custom_Fields(
			new WPML_Sync_Custom_Fields(
				$wpdb,
				new WPML_Translation_Element_Factory( $sitepress ),
				$sitepress->get_custom_fields_translation_settings( WPML_COPY_CUSTOM_FIELD )
			)
		);

		$hooks[] = new WPML_API_Hook_Links( new WPML_Post_Status_Display_Factory( $sitepress ) );

		return $hooks;
	}
}