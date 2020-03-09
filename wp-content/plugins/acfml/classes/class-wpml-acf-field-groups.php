<?php


class WPML_ACF_Field_Groups {
	private $sitepress;
	const POST_TYPE = 'acf-field-group';
	const DEFAULT_EDITOR_OPTION_NAME = 'acfml_field_groups_default_editor';

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function register_hooks() {
		if ( ! get_option( self::DEFAULT_EDITOR_OPTION_NAME ) ) {
			add_action( 'wpml_tm_loaded', array( $this, 'translate_field_groups_with_wp_editor' ) );
		}

		// ATE/CTE wizard resets this option so cover the case when it is run long after activating TM and ACFML plugins
		add_filter( 'wp_ajax_wpml_tm_wizard_done', array( $this, 'translate_field_groups_with_wp_editor' ), 1 );
	}

	/**
	 * Set translation mode for acf-field-group post type to 'native editor'
	 * but do it only once so user can change this if really must
	 */
	public function translate_field_groups_with_wp_editor() {
		$tm_settings = $this->sitepress->get_setting( 'translation-management', [] );
		$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ self::POST_TYPE ] = true;
		$this->sitepress->set_setting( 'translation-management', $tm_settings, true );
		WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option( self::POST_TYPE );
		update_option( self::DEFAULT_EDITOR_OPTION_NAME, true );
	}
}