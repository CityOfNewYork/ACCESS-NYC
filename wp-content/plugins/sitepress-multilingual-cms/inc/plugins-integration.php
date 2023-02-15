<?php

$action_filter_loader = new WPML_Action_Filter_Loader();
$action_filter_loader->load(
	array(
		'WPML_Compatibility_Factory',
	)
);

add_action( 'plugins_loaded', 'wpml_plugins_integration_setup', 10 );

/**
 * Loads compatibility classes for active plugins.
 */
function wpml_plugins_integration_setup() {
	global $sitepress, $wpdb;

	$factories_to_load = [];

	// bbPress integration.
	if ( class_exists( 'bbPress' ) ) {
		$wpml_bbpress_api     = new WPML_BBPress_API();
		$wpml_bbpress_filters = new WPML_BBPress_Filters( $wpml_bbpress_api );
		$wpml_bbpress_filters->add_hooks();
	}

	// NextGen Gallery.
	if ( defined( 'NEXTGEN_GALLERY_PLUGIN_VERSION' ) ) {
		// Todo: do not include files: move to autoloaded classes.
		require_once WPML_PLUGIN_PATH . '/inc/plugin-integration-nextgen.php';
	}

	if ( class_exists( 'GoogleSitemapGeneratorLoader' ) ) {
		$wpml_google_sitemap_generator = new WPML_Google_Sitemap_Generator( $wpdb, $sitepress );
		$wpml_google_sitemap_generator->init_hooks();
	}

	if ( class_exists( 'Tiny_Plugin' ) ) {
		$factories_to_load[] = 'WPML_Compatibility_Tiny_Compress_Images_Factory';
	}

	// phpcs:disable WordPress.NamingConventions.ValidVariableName
	global $DISQUSVERSION;
	if ( $DISQUSVERSION ) {
		$factories_to_load[] = 'WPML_Compatibility_Disqus_Factory';
	}
	// phpcs:enable

	if ( defined( 'GOOGLESITEKIT_VERSION' ) ) {
		$factories_to_load[] = \WPML\Compatibility\GoogleSiteKit\Hooks::class;
	}

	$action_filter_loader = new WPML_Action_Filter_Loader();
	$action_filter_loader->load( $factories_to_load );
}

add_action( 'after_setup_theme', 'wpml_themes_integration_setup' );

/**
 * Loads compatibility classes for active themes.
 */
function wpml_themes_integration_setup() {

	$actions = [];

	if ( function_exists( 'twentyseventeen_panel_count' ) && ! function_exists( 'twentyseventeen_translate_panel_id' ) ) {
		$wpml_twentyseventeen = new WPML_Compatibility_2017();
		$wpml_twentyseventeen->init_hooks();
	}

	$action_filter_loader = new WPML_Action_Filter_Loader();
	$action_filter_loader->load( $actions );
}

