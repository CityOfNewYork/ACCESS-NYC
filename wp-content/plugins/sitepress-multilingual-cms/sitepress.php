<?php
/*
Plugin Name: WPML Multilingual CMS
Plugin URI: https://wpml.org/
Description: WPML Multilingual CMS | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-3-8-2/">WPML 3.8.2 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 3.8.2
Plugin Slug: sitepress-multilingual-cms
*/

if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$is_not_network_admin = ! function_exists( 'is_multisite' ) || ! is_multisite() || ! is_network_admin();
if ( defined( 'ICL_SITEPRESS_VERSION' ) || ( (bool) get_option( '_wpml_inactive' ) && $is_not_network_admin ) ) {
	return;
}

define( 'ICL_SITEPRESS_VERSION', '3.8.2' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file
//define('ICL_SITEPRESS_DEV_VERSION', '3.4-dev');
define( 'WPML_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPML_PLUGIN_FOLDER', dirname( WPML_PLUGIN_BASENAME ) );
define( 'WPML_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . WPML_PLUGIN_FOLDER );
define( 'WPML_PLUGIN_FILE', basename( WPML_PLUGIN_BASENAME ) );

/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_BASENAME` instead */
define( 'ICL_PLUGIN_FULL_PATH', WPML_PLUGIN_BASENAME );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_FOLDER` instead */
define( 'ICL_PLUGIN_FOLDER', WPML_PLUGIN_FOLDER );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_PATH` instead */
define( 'ICL_PLUGIN_PATH', WPML_PLUGIN_PATH );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_FILE` instead */
define( 'ICL_PLUGIN_FILE', WPML_PLUGIN_FILE );

//PHP 5.2 backward compatibility
if ( ! defined( 'FILTER_SANITIZE_FULL_SPECIAL_CHARS' ) ) {
	define( 'FILTER_SANITIZE_FULL_SPECIAL_CHARS', FILTER_SANITIZE_STRING );
}
require WPML_PLUGIN_PATH . '/inc/functions-helpers.php';

$autoloader_dir = WPML_PLUGIN_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

$WPML_Dependencies = WPML_Dependencies::get_instance();

require WPML_PLUGIN_PATH . '/inc/wpml-private-actions.php';
require WPML_PLUGIN_PATH . '/inc/functions.php';
require WPML_PLUGIN_PATH . '/inc/functions-sanitation.php';
require WPML_PLUGIN_PATH . '/inc/functions-security.php';
require WPML_PLUGIN_PATH . '/inc/wpml-post-comments.class.php';
require WPML_PLUGIN_PATH . '/inc/icl-admin-notifier.php';

if ( ! function_exists( 'filter_input' ) ) {
	wpml_set_plugin_as_inactive();
	add_action( 'admin_notices', 'wpml_missing_filter_input_notice' );

	return;
}

$icl_plugin_url = untrailingslashit( plugin_dir_url( __FILE__ ) );
if ( (bool) wpml_get_setting_filter( array(), 'language_domains' ) === true && isset( $_SERVER['HTTP_HOST'] ) ) {
	global $wpdb, $wpml_include_url_filter;

	$wpml_include_url_filter = new WPML_Include_Url( $wpdb, $_SERVER['HTTP_HOST'] );
	$icl_plugin_url          = $wpml_include_url_filter->filter_include_url( $icl_plugin_url );
}
define( 'ICL_PLUGIN_URL', $icl_plugin_url );

require WPML_PLUGIN_PATH . '/inc/template-functions.php';
require WPML_PLUGIN_PATH . '/inc/js-scripts.php';
require WPML_PLUGIN_PATH . '/inc/lang-data.php';
require WPML_PLUGIN_PATH . '/inc/setup/sitepress-setup.class.php';

require WPML_PLUGIN_PATH . '/inc/not-compatible-plugins.php';
if ( ! empty( $icl_ncp_plugins ) ) {
	return;
}

require WPML_PLUGIN_PATH . '/inc/setup/sitepress-schema.php';

require WPML_PLUGIN_PATH . '/inc/functions-load.php';
require WPML_PLUGIN_PATH . '/inc/constants.php';
require WPML_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-translations.class.php';
require WPML_PLUGIN_PATH . '/inc/functions-troubleshooting.php';
require WPML_PLUGIN_PATH . '/menu/term-taxonomy-menus/taxonomy-translation-display.class.php';
require WPML_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-translation.class.php';

require WPML_PLUGIN_PATH . '/inc/post-translation/wpml-post-translation.class.php';
require WPML_PLUGIN_PATH . '/inc/post-translation/wpml-admin-post-actions.class.php';
require WPML_PLUGIN_PATH . '/inc/post-translation/wpml-frontend-post-actions.class.php';

require WPML_PLUGIN_PATH . '/inc/url-handling/wpml-url-filters.class.php';
require WPML_PLUGIN_PATH . '/inc/utilities/wpml-languages.class.php';
require WPML_PLUGIN_PATH . '/menu/post-menus/post-edit-screen/wpml-meta-boxes-post-edit-html.class.php';

load_essential_globals();

require WPML_PLUGIN_PATH . '/inc/query-filtering/wpml-query-utils.class.php';
require WPML_PLUGIN_PATH . '/sitepress.class.php';
require WPML_PLUGIN_PATH . '/inc/query-filtering/wpml-query-filter.class.php';
require WPML_PLUGIN_PATH . '/inc/hacks.php';
require WPML_PLUGIN_PATH . '/inc/upgrade.php';
require WPML_PLUGIN_PATH . '/inc/language-switcher.php';
require WPML_PLUGIN_PATH . '/inc/import-xml.php';

// using a plugin version that the db can't be upgraded to
if ( defined( 'WPML_UPGRADE_NOT_POSSIBLE' ) && WPML_UPGRADE_NOT_POSSIBLE ) {
	return;
}

if ( is_admin() || defined( 'XMLRPC_REQUEST' ) ) {
	require WPML_PLUGIN_PATH . '/lib/icl_api.php';
	require WPML_PLUGIN_PATH . '/inc/utilities/xml2array.php';
} elseif ( preg_match( '#wp-comments-post\.php$#', $_SERVER['REQUEST_URI'] ) ) {
	require_once WPML_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php';
}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
	if ( false === get_option( 'icl_sitepress_version', false ) && isset( $wpmu_sitewide_plugins[ WPML_PLUGIN_BASENAME ] ) ) {
		icl_sitepress_activate();
	}

	include_once WPML_PLUGIN_PATH . '/inc/functions-network.php';
	if ( get_option( '_wpml_inactive', false ) && isset( $wpmu_sitewide_plugins[ WPML_PLUGIN_BASENAME ] ) && $is_not_network_admin ) {
		wpml_set_plugin_as_inactive();

		return;
	}
}

if ( ! wp_next_scheduled( 'update_wpml_config_index' ) ) {
	//Set cron job to update WPML config index file from CDN
	wp_schedule_event( time(), 'daily', 'update_wpml_config_index' );
}
/** @var WPML_Post_Translation $wpml_post_translations */
global $sitepress, $wpdb, $wpml_url_filters, $wpml_post_translations, $wpml_term_translations, $wpml_url_converter, $wpml_language_resolution, $wpml_slug_filter, $wpml_cache_factory;

$wpml_cache_factory = new WPML_Cache_Factory();

$sitepress = new SitePress();

$action_filter_loader = new WPML_Action_Filter_Loader();

if ( $sitepress->is_setup_complete() ) {
	$actions = array(
		'WPML_Copy_Once_Custom_Field_Factory',
		'WPML_Adjacent_Links_Hooks_Factory',
		'WPML_Widgets_Support_Factory',
		'WPML_End_User_Loader_Factory',
		'WPML_End_User_Confirmation_Factory',
		'WPML_Admin_Resources_Hooks_Factory',
		'WPML_Themes_Plugin_Localization_UI_Hooks_Factory',
		'WPML_Theme_Plugin_Localization_Options_Ajax_Factory',
	);
	$action_filter_loader->load( $actions );
}

$sitepress->load_core_tm();

$wpml_wp_comments = new WPML_WP_Comments( $sitepress );
$wpml_wp_comments->add_hooks();

new WPML_Global_AJAX( $sitepress );
$wpml_wp_api = $sitepress->get_wp_api();
if ( $wpml_wp_api->is_support_page() ) {
	new WPML_Support_Page( $wpml_wp_api );
}

wpml_load_query_filter( icl_get_setting( 'setup_complete' ) );
$wpml_canonicals       = new WPML_Canonicals( $sitepress );
$wpml_canonicals_hooks = new WPML_Canonicals_Hooks( $sitepress, $wpml_url_converter );
$wpml_canonicals_hooks->add_hooks();
$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $wpml_canonicals, $sitepress, new WPML_Debug_BackTrace( $wpml_wp_api->phpversion(), 8 ) );
wpml_load_request_handler( is_admin(),
                           $wpml_language_resolution->get_active_language_codes(),
                           $sitepress->get_default_language() );

$tf_settings_read = new WPML_TF_Settings_Read();
/** @var WPML_TF_Settings $tf_settings */
$tf_settings                 = $tf_settings_read->get( 'WPML_TF_Settings' );
$translation_feedback_module = new WPML_TF_Module( $action_filter_loader, $tf_settings );
$translation_feedback_module->run();

require WPML_PLUGIN_PATH . '/inc/url-handling/wpml-slug-filter.class.php';
$wpml_slug_filter = new WPML_Slug_Filter( $wpdb, $sitepress, $wpml_post_translations );
/** @var array $sitepress_settings */
$sitepress_settings = $sitepress->get_settings();
wpml_load_term_filters();
wpml_maybe_setup_post_edit();

require WPML_PLUGIN_PATH . '/modules/cache-plugins-integration/cache-plugins-integration.php';
require WPML_PLUGIN_PATH . '/inc/plugins-integration.php';

if ( is_admin() ) {
	activate_installer( $sitepress );
	if ( $sitepress->get_setting( 'setup_complete' ) ) {
		setup_admin_menus();
	}
}

if ( ! empty( $sitepress_settings['automatic_redirect'] ) ) {
	$wpml_browser_redirect = new WPML_Browser_Redirect( $sitepress );
	$wpml_browser_redirect->init_hooks();
}

if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	/** @var wp_xmlrpc_server $wp_xmlrpc_server */
	global $sitepress;
	$wpml_xmlrpc = new WPML_XMLRPC( $sitepress );
	$wpml_xmlrpc->init_hooks();
}

if ( $sitepress->get_wp_api()
               ->is_admin() ) {
	wpml_get_admin_notices();
}

// activation hook
register_deactivation_hook( WPML_PLUGIN_PATH . '/' . WPML_PLUGIN_FILE, 'icl_sitepress_deactivate' );

add_filter( 'plugin_action_links', 'icl_plugin_action_links', 10, 2 );

$WPML_Users_Languages_Dependencies = new WPML_Users_Languages_Dependencies( $sitepress );

function wpml_init_language_switcher() {
	global $wpml_language_switcher, $sitepress;

	$wpml_language_switcher = new WPML_Language_Switcher( $sitepress );
	$wpml_language_switcher->init_hooks();
}

add_action( 'wpml_loaded', 'wpml_init_language_switcher' );

if ( $sitepress ) {
	add_action( 'init', 'wpml_integrations_requirements' );

	if ( ! $wpml_wp_api->is_front_end() ) {
		$languages_ajax = new WPML_Languages_AJAX( $sitepress );
		$languages_ajax->ajax_hooks();
	}
}

function wpml_integrations_requirements() {
	global $sitepress;

	$pbr = new WPML_Integrations_Requirements( $sitepress );
	$pbr->init_hooks();
}

function wpml_upgrade() {
	global $wpdb, $sitepress;
	$factory = new WPML_Upgrade_Command_Factory( $wpdb, $sitepress );

	$commands = array(
		new WPML_Upgrade_Command_Definition( 'WPML_Upgrade_Localization_Files', array( $sitepress ), array( 'admin' ) ),
		new WPML_Upgrade_Command_Definition( 'WPML_Upgrade_Fix_Non_Admin_With_Admin_Cap', array(), array( 'admin' ) ),
	);

	$upgrade = new WPML_Upgrade( $commands, $sitepress, $factory );
	$upgrade->run();
}

add_action( 'admin_init', 'wpml_upgrade' );

function wpml_troubleshoot_action_load() {
	global $sitepress;
	$wpml_troubleshoot_action = new WPML_Troubleshoot_Action();
	if ( $wpml_troubleshoot_action->is_valid_request() ) {
		$wpml_troubleshoot_sync_posts_taxonomies = new WPML_Troubleshoot_Sync_Posts_Taxonomies( $sitepress, new WPML_Term_Translation_Utils( $sitepress ) );
		$wpml_troubleshoot_sync_posts_taxonomies->run();
	}
}

add_action( 'admin_init', 'wpml_troubleshoot_action_load' );

function wpml_init_language_cookie_settings() {
	global $sitepress;

	$wpml_cookie_setting = new WPML_Cookie_Setting( $sitepress );

	if ( $sitepress->get_setting( 'setup_complete' )
	     && $sitepress->get_wp_api()
	                  ->is_core_page( 'languages.php' ) ) {
		$wpml_cookie_admin_scripts = new WPML_Cookie_Admin_Scripts();
		$wpml_cookie_admin_scripts->enqueue();

		$template_paths       = array( ICL_PLUGIN_PATH . '/templates/cookie-setting' );
		$twig_template        = new WPML_Twig_Template_Loader( $template_paths );
		$wpml_coolie_admin_ui = new WPML_Cookie_Admin_UI( $twig_template->get_template(), $wpml_cookie_setting );
		$wpml_coolie_admin_ui->add_hooks();
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$wpml_cookie_setting_ajax = new WPML_Cookie_Setting_Ajax( $wpml_cookie_setting );
		$wpml_cookie_setting_ajax->add_hooks();
	}
}

add_action( 'admin_init', 'wpml_init_language_cookie_settings' );

function wpml_check_php_version() {
	if ( 'index.php' !== $GLOBALS['pagenow'] && current_user_can( 'manage_options' ) ) {
		return;
	}

	add_filter( 'whip_hosting_page_url_wordpress', '__return_true' );
	whip_wp_check_versions( array( 'php' => '>=5.3', ) );
}

add_action( 'plugins_loaded', 'wpml_check_php_version' );

add_action( 'activated_plugin', 'wpml_core_loads_first' );
function wpml_core_loads_first() {
	$path    = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
	$plugins = get_option( 'active_plugins' );
	if ( $plugins && $key = array_search( $path, $plugins, true ) ) {
		array_splice( $plugins, $key, 1 );
		array_unshift( $plugins, $path );
		update_option( 'active_plugins', $plugins );
	}
}