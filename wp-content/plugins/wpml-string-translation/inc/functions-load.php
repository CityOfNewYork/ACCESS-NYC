<?php
require WPML_ST_PATH . '/inc/functions.php';
require WPML_ST_PATH . '/inc/private-actions.php';
require WPML_ST_PATH . '/inc/private-filters.php';

/**
 * @return WPML_Admin_Texts
 */
function wpml_st_load_admin_texts() {
	global $wpml_st_admin_texts;

	if ( ! $wpml_st_admin_texts ) {
		global $iclTranslationManagement, $WPML_String_Translation;
		$wpml_st_admin_texts = new WPML_Admin_Texts( $iclTranslationManagement, $WPML_String_Translation );
	}

	return $wpml_st_admin_texts;
}

/**
 * @return WPML_Slug_Translation
 */
function wpml_st_load_slug_translation( ) {
	global $wpml_slug_translation, $sitepress, $wpdb;

	if ( ! isset( $wpml_slug_translation ) ) {

		$wpml_slug_translation = new WPML_Slug_Translation( $sitepress, $wpdb, WPML_Get_LS_Languages_Status::get_instance() );

		add_action( 'init', array( $wpml_slug_translation, 'init' ), - 1000 );
	}

	return $wpml_slug_translation;
}

/**
 * @return WPML_ST_String_Factory
 */
function wpml_st_load_string_factory() {
	global $wpml_st_string_factory, $wpdb;

	if ( ! isset( $wpml_st_string_factory ) ) {
		$wpml_st_string_factory = new WPML_ST_String_Factory( $wpdb );
	}

	return $wpml_st_string_factory;
}
