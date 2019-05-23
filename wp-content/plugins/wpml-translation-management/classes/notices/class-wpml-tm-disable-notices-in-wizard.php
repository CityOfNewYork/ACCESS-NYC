<?php

class WPML_TM_Disable_Notices_In_Wizard {

	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	/** @var WPML_Translation_Management $wpml_translation_management */
	private $wpml_translation_management;

	public function __construct( WPML_WP_API $wp_api, WPML_Translation_Management $wpml_translation_management ) {
		$this->wp_api = $wp_api;
		$this->wpml_translation_management = $wpml_translation_management;
	}

	public function add_hooks() {
		if ( $this->wp_api->is_tm_page() && $this->wpml_translation_management->should_show_wizard() ) {
			add_action( 'admin_print_scripts', array( $this, 'disable_notices' ) );
		}
	}

	public function disable_notices() {
		global $wp_filter;

		unset( $wp_filter['user_admin_notices'], $wp_filter['admin_notices'], $wp_filter['all_admin_notices'] );
	}
}