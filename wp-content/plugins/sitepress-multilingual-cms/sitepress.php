<?php
/**
 * Plugin Name: WPML Multilingual CMS
 * Plugin URI: https://wpml.org/
 * Description: WPML Multilingual CMS | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-4-6-10/">WPML 4.6.10 release notes</a>
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Version: 4.6.10
 * Plugin Slug: sitepress-multilingual-cms
 *
 * @package WPML\Core
 */

use WPML\Container\Config;
use function WPML\Container\share;
use function WPML\FP\partial;

if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$is_not_network_admin = ! function_exists( 'is_multisite' ) || ! is_multisite() || ! is_network_admin();
if ( defined( 'ICL_SITEPRESS_VERSION' ) || ( (bool) get_option( '_wpml_inactive' ) && $is_not_network_admin ) ) {
	return;
}

require_once 'classes/requirements/WordPress.php';
if ( ! \WPML\Requirements\WordPress::checkMinimumRequiredVersion() ) {
	return;
}

define( 'ICL_SITEPRESS_VERSION', '4.6.10' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file
// define('ICL_SITEPRESS_DEV_VERSION', '3.4-dev');
define( 'WPML_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPML_PLUGIN_FOLDER', dirname( WPML_PLUGIN_BASENAME ) );
define( 'WPML_PLUGIN_PATH', __DIR__ );
define( 'WPML_PLUGINS_DIR', realpath( __DIR__ . '/..' ) );
define( 'WPML_PLUGIN_FILE', basename( WPML_PLUGIN_BASENAME ) );

/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_BASENAME` instead */
define( 'ICL_PLUGIN_FULL_PATH', WPML_PLUGIN_BASENAME );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_FOLDER` instead */
define( 'ICL_PLUGIN_FOLDER', WPML_PLUGIN_FOLDER );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_PATH` instead */
define( 'ICL_PLUGIN_PATH', WPML_PLUGIN_PATH );
/** @deprecated since 3.7.0 and will be removed in 3.8.0, use `WPML_PLUGIN_FILE` instead */
define( 'ICL_PLUGIN_FILE', WPML_PLUGIN_FILE );

require_once __DIR__ . '/inc/functions-helpers.php';

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', 'wpml_disable_outdated_plugins', -PHP_INT_MAX );

function wpml_disable_outdated_plugins() {
	$dependencies = file_get_contents(
		dirname( __FILE__ ) . '/wpml-dependencies.json'
	);

	if ( ! $dependencies ) {
		return;
	}

	WPML_Plugins_Check::disable_outdated(
		$dependencies,
		defined( 'WPML_TM_VERSION' ) ? WPML_TM_VERSION : '1.0',
		defined( 'WPML_ST_VERSION' ) ? WPML_ST_VERSION : '1.0',
		defined( 'WCML_VERSION' ) ? WCML_VERSION : '1.0'
	);
}

$WPML_Dependencies = WPML_Dependencies::get_instance();

require_once __DIR__ . '/inc/wpml-private-actions.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/functions-sanitation.php';
require_once __DIR__ . '/inc/functions-security.php';
require_once __DIR__ . '/inc/wpml-post-comments.class.php';
require_once __DIR__ . '/inc/icl-admin-notifier.php';
require_once __DIR__ . '/classes/container/functions.php';

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

require_once __DIR__ . '/inc/template-functions.php';
require_once __DIR__ . '/inc/js-scripts.php';
require_once __DIR__ . '/inc/lang-data.php';
require_once __DIR__ . '/inc/setup/sitepress-setup.class.php';

require_once __DIR__ . '/inc/not-compatible-plugins.php';
if ( ! empty( $icl_ncp_plugins ) ) {
	return;
}

require_once __DIR__ . '/inc/setup/sitepress-schema.php';

require_once __DIR__ . '/inc/functions-load.php';
require_once __DIR__ . '/inc/constants.php';

require_once __DIR__ . '/vendor/otgs/ui/loader.php';
otgs_ui_initialize( __DIR__ . '/vendor/otgs/ui', ICL_PLUGIN_URL . '/vendor/otgs/ui' );

$vendor_root_url = ICL_PLUGIN_URL . '/vendor';
require_once __DIR__ . '/vendor/otgs/icons/loader.php';

require_once __DIR__ . '/inc/taxonomy-term-translation/wpml-term-translations.class.php';
require_once __DIR__ . '/inc/functions-troubleshooting.php';
require_once __DIR__ . '/menu/term-taxonomy-menus/taxonomy-translation-display.class.php';
require_once __DIR__ . '/inc/taxonomy-term-translation/wpml-term-translation.class.php';

require_once __DIR__ . '/inc/post-translation/wpml-post-translation.class.php';
require_once __DIR__ . '/inc/post-translation/wpml-admin-post-actions.class.php';
require_once __DIR__ . '/inc/post-translation/wpml-frontend-post-actions.class.php';

require_once __DIR__ . '/inc/utilities/wpml-languages.class.php';
require_once __DIR__ . '/menu/post-menus/post-edit-screen/wpml-meta-boxes-post-edit-html.class.php';

load_essential_globals();

require_once __DIR__ . '/sitepress.class.php';
require_once __DIR__ . '/inc/hacks.php';
require_once __DIR__ . '/inc/upgrade.php';
require_once __DIR__ . '/inc/language-switcher.php';
require_once __DIR__ . '/inc/import-xml.php';
require_once __DIR__ . '/inc/utilities/xml2array.php';

// using a plugin version that the db can't be upgraded to.
if ( defined( 'WPML_UPGRADE_NOT_POSSIBLE' ) && WPML_UPGRADE_NOT_POSSIBLE ) {
	return;
}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
	if ( false === get_option( 'icl_sitepress_version', false ) && isset( $wpmu_sitewide_plugins[ WPML_PLUGIN_BASENAME ] ) ) {
		icl_sitepress_activate();
	}

	include_once __DIR__ . '/inc/functions-network.php';
	if ( get_option( '_wpml_inactive', false ) && isset( $wpmu_sitewide_plugins[ WPML_PLUGIN_BASENAME ] ) && $is_not_network_admin ) {
		wpml_set_plugin_as_inactive();

		return;
	}
}

if ( ! wp_next_scheduled( 'update_wpml_config_index' ) ) {
	// Set cron job to update WPML config index file from CDN.
	wp_schedule_event( time(), 'daily', 'update_wpml_config_index' );
}
/** @var WPML_Post_Translation $wpml_post_translations */
/** @var WPML_Language_Resolution $wpml_language_resolution */
global $sitepress, $wpdb, $wpml_url_filters, $wpml_post_translations, $wpml_term_translations, $wpml_url_converter, $wpml_language_resolution, $wpml_slug_filter, $wpml_cache_factory;

$wpml_cache_factory = new WPML_Cache_Factory();

WPML\Container\share( Config::getSharedInstances() );
WPML\Container\share( Config::getSharedClasses() );
WPML\Container\alias( Config::getAliases() );
WPML\Container\delegate( Config::getDelegated() );
$sitepress = WPML\Container\make( '\SitePress' );

$action_filter_loader = new WPML_Action_Filter_Loader();
$action_filter_loader->load( [
	\WPML\Ajax\Factory::class,
	\WPML\Installer\AddSiteUrl::class,
] );

if ( $sitepress->is_setup_complete() ) {
	$actions = [
		'WPML_Copy_Once_Custom_Field',
		'WPML_Adjacent_Links_Hooks_Factory',
		'WPML_Widgets_Support_Factory',
		'WPML_Admin_Resources_Hooks',
		'WPML_Themes_Plugin_Localization_UI_Hooks_Factory',
		'WPML_Theme_Plugin_Localization_Options_Ajax',
		'WPML_Archives_Query',
		'WPML_Fix_Links_In_Display_As_Translated_Content',
		'WPML_Display_As_Translated_Tax_Query_Factory',
		'WPML_Tax_Permalink_Filters_Factory',
		'WPML_Display_As_Translated_Snippet_Filters_Factory',
		'WPML_Upgrade_Loader_Factory',
		'WPML_Post_Edit_Terms_Hooks_Factory',
		'WPML_Attachments_Urls_With_Identical_Slugs_Factory',
		'WPML_API_Hooks_Factory',
		'WPML_Display_As_Translated_Message_For_New_Post_Factory',
		'WPML_Custom_Fields_Post_Meta_Info_Factory',
		'WPML_Display_As_Translated_Default_Lang_Messages_Factory',
		'WPML_Absolute_Url_Persisted_Filters_Factory',
		'WPML_Meta_Boxes_Post_Edit_Ajax_Factory',
		'WPML_Privacy_Content_Factory',
		'WPML_Custom_Columns_Factory',
		'WPML_Config_Shortcode_List',
		'WPML_Config_Built_With_Page_Builders',
		'WPML_Endpoints_Support_Factory',
		'WPML_Installer_Domain_URL_Factory',
		'WPML_REST_Extend_Args_Factory',
		'WPML_WP_Options_General_Hooks_Factory',
		'WPML_WP_In_Subdir_URL_Filters_Factory',
		'WPML_Table_Collate_Fix',
		\WPML\Options\Reset::class,
		'\WPML\Notices\DismissNotices',
		'\WPML\Ajax\Locale',
		\WPML\PostTranslation\SpecialPage\Hooks::class,
		\WPML\LanguageSwitcher\AjaxNavigation\Hooks::class,
		\WPML\BrowserLanguageRedirect\Dialog::class,
		\WPML\UrlHandling\WPLoginUrlConverterFactory::class,
		\WPML\Roles::class,
		\WPML\Languages\UI::class,
		\WPML\Settings\UI::class,
		\WPML\AdminMenu\Redirect::class,
		\WPML\Core\Menu\Translate::class,
		\WPML\TaxonomyTermTranslation\AutoSync::class,
		\WPML\FullSiteEditing\BlockTemplates::class,
		\WPML\AdminLanguageSwitcher\DisableWpLanguageSwitcher::class,
		\WPML\AdminLanguageSwitcher\AdminLanguageSwitcher::class,
		\WPML\TM\Troubleshooting\Loader::class,
		\WPML\TaxonomyTermTranslation\Hooks::class,
		\WPML\BlockEditor\Loader::class,
		\WPML\TM\ATE\Hooks\LanguageMappingCache::class,
		\WPML\BackgroundTask\BackgroundTaskLoader::class,
		\WPML\Core\PostTranslation\SyncTranslationDocumentStatus::class,
		\WPML\Utilities\DebugLog::class,
		\WPML\Notices\ExportImport\Notice::class,
	];
	$action_filter_loader->load( $actions );

	if ( $sitepress->is_translated_post_type( 'attachment' ) ) {
		$media_actions = [
			'WPML_Attachment_Action_Factory',
			'WPML_Media_Attachments_Duplication_Factory',
			\WPML\Media\Duplication\HooksFactory::class,
			'WPML_Deactivate_Old_Media_Factory',
			'WPML_Display_As_Translated_Attachments_Query_Factory',
			'WPML_Media_Settings_Factory',
			\WPML\Media\Loader::class,
			\WPML\Media\FrontendHooks::class,
		];

		$action_filter_loader->load( $media_actions );
	}

	$rest_factories = [
		'WPML_REST_Posts_Hooks_Factory',
		'WPML\Core\REST\RewriteRules',
		\WPML\REST\XMLConfig\Custom\Factory::class,
	];

	$action_filter_loader->load( $rest_factories );

	// On posts listing page.
	add_action(
		'load-edit.php',
		function() {
			new WPML_Posts_Listing_Page();
		}
	);
} else {
	$action_filter_loader->load( [
		\WPML\Setup\DisableNotices::class,
		\WPML\Installer\DisableRegisterNow::class,
	] );
}

$sitepress->load_core_tm();

$wpml_wp_comments = new WPML_WP_Comments( $sitepress );
$wpml_wp_comments->add_hooks();

new WPML_Global_AJAX( $sitepress );
/** @var \WPML_WP_API $wpml_wp_api */
$wpml_wp_api = $sitepress->get_wp_api();
if ( $wpml_wp_api->is_support_page() ) {
	new WPML_Support_Page( $wpml_wp_api );
}

wpml_load_query_filter( $sitepress->get_setting( 'setup_complete' ) );
$wpml_canonicals       = new WPML_Canonicals( $sitepress, new WPML_Translation_Element_Factory( $sitepress ) );
$wpml_canonicals_hooks = new WPML_Canonicals_Hooks(
	$sitepress,
	$wpml_url_converter,
	array(
		'WPML_Root_Page',
		'is_current_request_root',
	)
);
$wpml_canonicals_hooks->add_hooks();
$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $wpml_canonicals, $sitepress, new WPML_Debug_BackTrace( $wpml_wp_api->phpversion() ) );
share( [ $wpml_url_filters ] );
wpml_load_request_handler( is_admin(), $wpml_language_resolution->get_active_language_codes(), $sitepress->get_default_language() );

$tf_settings_read = new WPML_TF_Settings_Read();
/** @var WPML_TF_Settings $tf_settings */
$tf_settings                 = $tf_settings_read->get( 'WPML_TF_Settings' );
$translation_feedback_module = new WPML_TF_Module( $action_filter_loader, $tf_settings );
$translation_feedback_module->run();

require_once __DIR__ . '/inc/url-handling/wpml-slug-filter.class.php';
$wpml_slug_filter = new WPML_Slug_Filter( $wpdb, $sitepress, $wpml_post_translations );
/** @var array $sitepress_settings */
$sitepress_settings = $sitepress->get_settings();
wpml_load_term_filters();
// Add wpml_maybe_setup_post_edit() before wpml_home_url_init().
add_action( 'init', 'wpml_maybe_setup_post_edit', - 1 );

require_once __DIR__ . '/modules/cache-plugins-integration/cache-plugins-integration.php';
require_once __DIR__ . '/inc/plugins-integration.php';

activate_installer( $sitepress );

if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	if ( $sitepress->get_setting( 'setup_complete' ) ) {
		setup_admin_menus();
	}
}

if ( ! empty( $sitepress_settings['automatic_redirect'] ) ) {
	$wpml_browser_redirect = new WPML_Browser_Redirect( $sitepress );
	$wpml_browser_redirect->init_hooks();
}

if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	global $sitepress;
	$wpml_xmlrpc = new WPML_XMLRPC( $sitepress );
	$wpml_xmlrpc->init_hooks();
}

if ( $sitepress->get_wp_api()->is_admin() ) {
	wpml_get_admin_notices();
}

// activation hook
register_deactivation_hook( WPML_PLUGIN_PATH . '/' . WPML_PLUGIN_FILE, 'icl_sitepress_deactivate' );

add_filter( 'plugin_action_links', partial( 'icl_plugin_action_links', $sitepress ), 10, 2 );

if ( $sitepress->is_setup_complete() ) {
	$WPML_Users_Languages_Dependencies = new WPML_Users_Languages_Dependencies( $sitepress );
}

function wpml_init_cli() {
	$wpml_cli_bootstrap = new \WPML\CLI\Core\BootStrap();
	$wpml_cli_bootstrap->init();
}
function wpml_init_language_switcher() {
	global $wpml_language_switcher, $sitepress;

	$wpml_language_switcher = new WPML_Language_Switcher( $sitepress );
	$wpml_language_switcher->init_hooks();
}

function wpml_mlo_init() {
	global $sitepress, $wpdb;
	$array_helper    = new WPML_Multilingual_Options_Array_Helper();
	$utils           = new WPML_Multilingual_Options_Utils( $wpdb );
	$wpml_ml_options = new WPML_Multilingual_Options( $sitepress, $array_helper, $utils );
	$wpml_ml_options->init_hooks();
}

/**
 * @param SitePress $sitepress
 */
function wpml_loaded( $sitepress ) {
	if ( class_exists( 'WP_CLI' ) && defined( 'WP_CLI' ) && WP_CLI ) {
		wpml_init_cli();
	}
	$wpml_wp_api = $sitepress->get_wp_api();

	wpml_init_language_switcher();

	wpml_mlo_init();

	/**
	 * Also allow `troubleshooting.php` and `theme-localization.php` because we have direct AJAX calls
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-5167
	 */
	if (
		$wpml_wp_api->is_back_end()
		|| $wpml_wp_api->is_core_page( 'troubleshooting.php' )
		|| $wpml_wp_api->is_core_page( 'theme-localization.php' )
	) {
		$main_menu = new WPML_Main_Admin_Menu( $sitepress );
		$main_menu->configure();
	}
}

add_action( 'wpml_loaded', 'wpml_loaded' );

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

	if ( $sitepress->get_setting( 'setup_complete' ) && $sitepress->get_wp_api()->is_core_page( 'languages.php' ) ) {
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

$wpml_whip_requirements = new WPML_Whip_Requirements();
$wpml_whip_requirements->add_hooks();

add_action( 'activated_plugin', [ 'WPML\Plugins', 'loadCoreFirst' ] );

if ( ! defined('WPML_DO_NOT_LOAD_EMBEDDED_TM' ) || ! WPML_DO_NOT_LOAD_EMBEDDED_TM ) {
	WPML\Plugins::loadEmbeddedTM( $sitepress->is_setup_complete() );
}

if ( defined( 'WCML_VERSION') ) {
	WPML\Plugins::loadCoreFirst();
}

add_action( 'plugins_loaded', function() {
	require_once WPML_PLUGIN_PATH . '/addons/wpml-page-builders/loader.php';
}, PHP_INT_MAX );


