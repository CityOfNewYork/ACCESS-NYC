<?php

class WPML_TM_Translation_Roles_Section_Factory implements IWPML_TM_Admin_Section_Factory {

	public function create() {
		global $wpdb;

		$user_query_factory = new WPML_WP_User_Query_Factory();

		$translation_manager_settings = new WPML_Translation_Manager_Settings(
			new WPML_Translation_Manager_View(),
			new WPML_Translation_Manager_Records( $wpdb, $user_query_factory )
		);

		$translator_settings = new WPML_Translator_Settings_Proxy( array( $this, 'create_translator_settings' ) );

		return new WPML_TM_Translation_Roles_Section( $translation_manager_settings, $translator_settings );
	}

	public function create_translator_settings() {
		global $wpdb, $sitepress;

		do_action( 'wpml_tm_ate_synchronize_translators' );

		$user_query_factory = new WPML_WP_User_Query_Factory();

		return new WPML_Translator_Settings(
			new WPML_Translator_Records( $wpdb, $user_query_factory ),
			new WPML_Language_Collection( $sitepress, array_keys( $sitepress->get_active_languages() ) ),
			$sitepress->get_default_language()
		);
	}
}