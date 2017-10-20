<?php

add_action( 'plugins_loaded', 'wpml_plugins_integration_setup', 10 );

function wpml_plugins_integration_setup() {
	/** @var WPML_URL_Converter $wpml_url_converter */
	global $sitepress, $wpml_url_converter, $wpdb;
	// WPSEO integration
	if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '1.0.3', '>=' ) ) {
		$wpml_wpseo_xml_sitemap_filters = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );
		$wpml_wpseo_xml_sitemap_filters->init_hooks();
		$canonical     = new WPML_Canonicals( $sitepress );
		$wpseo_filters = new WPML_WPSEO_Filters( $canonical );
		$wpseo_filters->init_hooks();
	}
	if ( class_exists( 'bbPress' ) ) {
		$wpml_bbpress_api     = new WPML_BBPress_API();
		$wpml_bbpress_filters = new WPML_BBPress_Filters( $wpml_bbpress_api, $sitepress, $wpml_url_converter );
		$wpml_bbpress_filters->add_hooks();
	}

	// NextGen Gallery
	if ( defined( 'NEXTGEN_GALLERY_PLUGIN_VERSION' ) ) {
		//Todo: do not include files: move to autoloaded classes
		require_once WPML_PLUGIN_PATH . '/inc/plugin-integration-nextgen.php';
	}

	if ( defined( 'WPB_VC_VERSION' ) ) {
		$wpml_visual_composer = new WPML_Compatibility_Plugin_Visual_Composer( new WPML_Debug_BackTrace( PHP_VERSION, 12 ) );
		$wpml_visual_composer->add_hooks();
	}

	if ( class_exists( 'GoogleSitemapGeneratorLoader' ) ) {
		$wpml_google_sitemap_generator = new WPML_Google_Sitemap_Generator( $wpdb, $sitepress );
		$wpml_google_sitemap_generator->init_hooks();
	}
}

add_action( 'after_setup_theme', 'wpml_themes_integration_setup' );

function wpml_themes_integration_setup() {
	if ( function_exists( 'twentyseventeen_panel_count' ) && ! function_exists( 'twentyseventeen_translate_panel_id' ) ) {
		$wpml_twentyseventeen = new WPML_Compatibility_2017();
		$wpml_twentyseventeen->init_hooks();
	}

	if ( function_exists( 'avia_lang_setup' ) ) {
		global $iclTranslationManagement;
		$enfold = new WPML_Compatibility_Theme_Enfold( $iclTranslationManagement );
		$enfold->init_hooks();
	}

	if ( defined( 'ET_BUILDER_THEME' ) || defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
		global $sitepress;
		$divi = new WPML_Compatibility_Divi( $sitepress );
		$divi->add_hooks();
	}
}
