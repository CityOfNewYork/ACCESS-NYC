<?php

$action_filter_loader = new WPML_Action_Filter_Loader();
$action_filter_loader->load(
	array(
		'WPML_Compatibility_Factory',
	)
);

/**
 * Determines if we should load compatibility classes for wordpress-seo.
 */
function wpml_should_load_wpseo_classes() {
	global $sitepress;

	return $sitepress->get_wp_api()->defined( 'WPSEO_VERSION' )
		&& version_compare( $sitepress->get_wp_api()->constant( 'WPSEO_VERSION' ), '1.0.3', '>=' )
		&& ! $sitepress->get_wp_api()->defined( 'WPSEOML_VERSION' );
}

// We have to do this early because wordpress-seo does it early too.
if ( wpml_should_load_wpseo_classes() ) {
	$redirector = new WPML_WPSEO_Redirection_Old();
	if ( $redirector->is_redirection() ) {
		add_filter( 'wpml_skip_convert_url_string', '__return_true' );
	}
}

add_action( 'plugins_loaded', 'wpml_plugins_integration_setup', 10 );

/**
 * Loads compatibility classes for active plugins.
 */
function wpml_plugins_integration_setup() {
	/** @var WPML_URL_Converter $wpml_url_converter */
	global $sitepress, $wpml_url_converter, $wpdb, $pagenow;
	// WPSEO integration.
	if ( wpml_should_load_wpseo_classes() ) {
		$wpml_wpseo_xml_sitemap_filters = new WPML_WPSEO_XML_Sitemaps_Filter_Old( $sitepress, $wpml_url_converter );
		$wpml_wpseo_xml_sitemap_filters->init_hooks();
		$canonical     = new WPML_Canonicals( $sitepress, new WPML_Translation_Element_Factory( $sitepress ) );
		$wpseo_filters = new WPML_WPSEO_Filters_Old( $canonical );
		$wpseo_filters->init_hooks();
		$metabox_hooks = new WPML_WPSEO_Metabox_Hooks_Old( new WPML_Debug_BackTrace(), $wpml_url_converter, $pagenow );
		$metabox_hooks->add_hooks();

		$categories = new WPML_Compatibility_Wordpress_Seo_Categories_Old();
		$categories->add_hooks();
	}
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

	// WPBakery Page Builder (a.k.a. Visual Composer).
	if ( defined( 'WPB_VC_VERSION' ) ) {
		$wpml_visual_composer = new WPML_Compatibility_Plugin_Visual_Composer( new WPML_Debug_BackTrace( null, 12 ) );
		$wpml_visual_composer->add_hooks();

		$wpml_visual_composer_grid = new WPML_Compatibility_Plugin_Visual_Composer_Grid_Hooks(
			$sitepress,
			new WPML_Translation_Element_Factory( $sitepress )
		);
		$wpml_visual_composer_grid->add_hooks();
	}

	if ( class_exists( 'GoogleSitemapGeneratorLoader' ) ) {
		$wpml_google_sitemap_generator = new WPML_Google_Sitemap_Generator( $wpdb, $sitepress );
		$wpml_google_sitemap_generator->init_hooks();
	}

	$factories_to_load = array();

	if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
		$factories_to_load[] = 'WPML_Compatibility_Plugin_Fusion_Hooks_Factory';
		$factories_to_load[] = '\WPML\Compatibility\FusionBuilder\Frontend\Hooks';
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

	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		$factories_to_load[] = WPML_PB_Fix_Maintenance_Query::class;
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

	if ( function_exists( 'avia_lang_setup' ) ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName
		global $iclTranslationManagement;
		$enfold = new WPML_Compatibility_Theme_Enfold( $iclTranslationManagement );
		// phpcs:enable
		$enfold->init_hooks();
	}

	if ( defined( 'ET_BUILDER_THEME' ) || defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
		$actions[] = WPML_Compatibility_Divi::class;
		$actions[] = WPML\Compatibility\Divi\DynamicContent::class;
		$actions[] = WPML\Compatibility\Divi\Search::class;
		$actions[] = WPML\Compatibility\Divi\DiviOptionsEncoding::class;
		$actions[] = WPML\Compatibility\Divi\ThemeBuilderFactory::class;
	}

	if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
		$actions[] = WPML\Compatibility\FusionBuilder\DynamicContent::class;
	}

	$action_filter_loader = new WPML_Action_Filter_Loader();
	$action_filter_loader->load( $actions );
}

