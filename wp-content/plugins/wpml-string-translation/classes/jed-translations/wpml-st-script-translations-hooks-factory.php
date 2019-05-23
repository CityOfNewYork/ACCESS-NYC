<?php

class WPML_ST_Script_Translations_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_Frontend_Action_Loader {

	public function create() {
		global $wpdb;

		$hooks = array();

		// Initialize the file system, otherwise some constants are not set
		wp_filesystem_init();

		$wp_api     = new WPML_WP_API();
		$filesystem = $wp_api->get_wp_filesystem_direct();

		$jed_file_manager = new WPML_ST_JED_File_Manager(
			new WPML_ST_JED_Strings_Retrieve( $wpdb ),
			new WPML_ST_JED_File_Builder(),
			$filesystem,
			new WPML_Language_Records( $wpdb )
		);

		$hooks['update'] = $this->get_update_hooks( $jed_file_manager );

		if ( ! wpml_is_ajax() && ! $this->is_doing_rest_request() ) {
			$hooks['filtering'] = $this->get_filtering_hooks( $wp_api, $filesystem, $jed_file_manager );
		}

		return $hooks;
	}

	/**
	 * @param WPML_ST_JED_File_Manager $jed_file_manager
	 *
	 * @return WPML_ST_JED_File_Update_Hooks
	 */
	private function get_update_hooks( $jed_file_manager ) {
		global $wpdb;

		return new WPML_ST_JED_File_Update_Hooks(
			$jed_file_manager,
			new WPML_ST_JED_Locales_Domains_Mapper( $wpdb )
		);
	}

	/**
	 * @param WPML_WP_API $wp_api
	 * @param WP_Filesystem_Direct $filesystem
	 * @param WPML_ST_JED_File_Manager $jed_file_manager
	 *
	 * @return WPML_ST_Script_Translations_Hooks
	 */
	private function get_filtering_hooks( $wp_api, $filesystem, $jed_file_manager ) {
		global $wpdb;

		$files_dictionary = new WPML_ST_Translations_File_Dictionary(
			new WPML_ST_Translations_File_Dictionary_Storage_Table( $wpdb )
		);

		$wpml_file = new WPML_File( $wp_api, $filesystem );

		return new WPML_ST_Script_Translations_Hooks( $files_dictionary, $jed_file_manager, $wpml_file );
	}

	/**
	 * @return bool
	 */
	private function is_doing_rest_request() {
		/**
		 * We can't rely on REST_REQUEST constant because it is defined much later than this action
		 */
		return false !== strpos( $_SERVER['REQUEST_URI'], apply_filters( 'rest_url_prefix', 'wp-json' ) );
	}
}
