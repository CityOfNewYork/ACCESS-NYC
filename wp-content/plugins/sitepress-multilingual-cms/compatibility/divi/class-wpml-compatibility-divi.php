<?php

class WPML_Compatibility_Divi {
	/** @var SitePress */
	private $sitepress;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'load_resources_if_they_are_required' ), 10, 0 );

		if ( $this->sitepress->is_setup_complete() ) {
			add_action( 'admin_init', array( $this, 'display_warning_notice' ), 10, 0 );
		}
	}

	/**
	 * @return bool
	 */
	private function is_standard_editor_used() {
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );

		return ! isset( $tm_settings['doc_translation_method'] ) ||
		       ICL_TM_TMETHOD_MANUAL === $tm_settings['doc_translation_method'];
	}

	public function display_warning_notice() {
		$notices = wpml_get_admin_notices();

		if ( $this->is_standard_editor_used() ) {
			$notices->add_notice( new WPML_Compatibility_Divi_Notice() );
		} elseif ( $notices->get_notice( WPML_Compatibility_Divi_Notice::ID, WPML_Compatibility_Divi_Notice::GROUP ) ) {
			$notices->remove_notice( WPML_Compatibility_Divi_Notice::GROUP, WPML_Compatibility_Divi_Notice::ID );
		}
	}

	public function load_resources_if_they_are_required() {
		if ( ! isset( $_GET['page'] ) || ! is_admin() ) {
			return;
		}

		$pages = array( self::get_duplication_action_page() );
		if ( $this->is_tm_active() ) {
			$pages[] = self::get_translation_editor_page();
		}

		if ( in_array( $_GET['page'], $pages, true ) ) {
			$this->register_layouts();
		}
	}

	private static function get_translation_editor_page() {
		return WPML_TM_FOLDER . '/menu/translations-queue.php';
	}

	private static function get_duplication_action_page() {
		return WPML_PLUGIN_FOLDER . '/menu/languages.php';
	}

	private function is_tm_active() {
		return defined( 'WPML_TM_FOLDER' );
	}

	private function register_layouts() {
		if ( ! et_builder_should_load_framework() ) {
			et_builder_register_layouts();
		}
	}
}
