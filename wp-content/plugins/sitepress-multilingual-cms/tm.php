<?php

use WPML\TM\Notices\AteLockNotice;
use WPML\TM\ATE\ClonedSites\ReportAjax;
use WPML\TM\Settings\CustomFieldChangeDetector;

if ( defined( 'WPML_TM_VERSION' ) || get_option( '_wpml_inactive' ) ) {
	return;
}

define( 'WPML_TM_VERSION', '2.11.0' );

// Do not uncomment the following line!
// If you need to use this constant, use it in the wp-config.php file.
if ( ! defined( 'WPML_TM_PATH' ) ) {
	define( 'WPML_TM_PATH', dirname( __FILE__ ) );
}

function initialize_wpml_cache_factory() {
	global $wpml_cache_factory;

	$translator_filters = [ 'wpml_tm_add_translation_role', 'wpml_tm_remove_translation_role' ];
	$wpml_cache_factory->define( 'WPML_TM_Blog_Translators::get_raw_blog_translators', $translator_filters );

	$wpml_cache_factory->define( 'WPML_TM_Blog_Translators::has_translators', $translator_filters );
}

/**
 * Load plugin.
 *
 * @param SitePress $sitepress WPML main plugin instance.
 */
function wpml_tm_load( $sitepress = null ) {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	global $wpdb, $WPML_Translation_Management, $ICL_Pro_Translation;

	$is_admin   = is_admin();
	$is_xmlrpc  = defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	$is_rest    = wpml_is_rest_request();
	$is_cron    = defined( 'DOING_CRON' ) && DOING_CRON;
	$is_backend = $is_admin || $is_rest || $is_xmlrpc || wpml_is_cli() || $is_cron;

	require_once WPML_TM_PATH . '/inc/constants-tm.php';
	require_once WPML_TM_PATH . '/inc/functions-load.php';
	if ( $is_backend ) {
		require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-pro-translation.class.php';
		require_once WPML_TM_PATH . '/inc/js-tm-scripts.php';
		require_once WPML_TM_PATH . '/inc/deprecated-hooks.php';
	}

	new WPML_TM_Requirements();

	if ( ! $sitepress ) {
		global $sitepress;
	}

	\WPML\Container\share( \WPML\TM\Container\Config::getSharedClasses() );
	\WPML\Container\delegate( \WPML\TM\Container\Config::getDelegated() );

	if ( ! $sitepress || ! $sitepress instanceof SitePress || ! $sitepress->is_setup_complete() ) {
		return;
	}

	if ( $is_backend ) {
		require_once WPML_TM_PATH . '/menu/dashboard/wpml-tm-dashboard.class.php';
		require_once WPML_TM_PATH . '/menu/wpml-tm-menus.class.php';
	}

	$action_filter_loader = new WPML_Action_Filter_Loader();

	if ( $is_backend ) {
		$WPML_Translation_Management = wpml_translation_management();
		$WPML_Translation_Management->init();
		$WPML_Translation_Management->load();

		if ( ! $ICL_Pro_Translation ) {
			$job_factory         = wpml_tm_load_job_factory();
			$ICL_Pro_Translation = new WPML_Pro_Translation( $job_factory );
		}
	} else {
		do_action( 'wpml_tm_loaded' );
	}

	if ( $is_admin ) {
		$wpml_wp_api      = new WPML_WP_API();
		$TranslationProxy = new WPML_Translation_Proxy_API();
		new WPML_TM_Troubleshooting_Reset_Pro_Trans_Config( $sitepress, $TranslationProxy, $wpml_wp_api, $wpdb );
		new WPML_TM_Troubleshooting_Clear_TS( $wpml_wp_api );
		new WPML_TM_Promotions( $wpml_wp_api );

		if ( defined( 'DOING_AJAX' ) ) {
			$wpml_tm_options_ajax = new WPML_TM_Options_Ajax( $sitepress );
			$wpml_tm_options_ajax->ajax_hooks();

			$wpml_tm_pickup_mode_ajax = new WPML_TM_Pickup_Mode_Ajax( $sitepress, $ICL_Pro_Translation );
			$wpml_tm_pickup_mode_ajax->ajax_hooks();
		}
	}
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

	if ( class_exists( 'WPML_TF_Settings_Read' ) ) {
		$tf_settings_read = new WPML_TF_Settings_Read();
		/**
		 * Translation feedback settings.
		 *
		 * @var WPML_TF_Settings $tf_settings
		 */
		$tf_settings                 = $tf_settings_read->get( 'WPML_TF_Settings' );
		$translation_feedback_module = new WPML_TM_TF_Module( $action_filter_loader, $tf_settings );
		$translation_feedback_module->run();
	}

	$global_actions = [
		'WPML_TM_API_Hooks_Factory',
		'WPML_TM_ATE_Translator_Login_Factory',
		'WPML_TM_Default_Settings_Factory',
		'WPML_TM_Shortcodes_Catcher_Factory',
		'WPML_TM_Translation_Priorities_Factory',
		'WPML_TM_Word_Count_Hooks_Factory',
		'\WPML\TM\AdminBar\Hooks',
		\WPML\TM\AutomaticTranslation\Actions\ActionsFactory::class,
		\WPML\TM\ATE\Review\ReviewTranslation::class,
		\WPML\TM\ATE\Hooks\JobActionsFactory::class,
		'\WPML\ATE\Proxies\Widget',
		'WPML_TM_Upgrade_Loader_Factory',
	];
	$action_filter_loader->load( $global_actions );

	if ( $is_backend ) {
		$actions = [
			'WPML_TM_Jobs_Deadline_Estimate_AJAX_Action_Factory',
			'WPML_TM_Jobs_Deadline_Cron_Hooks_Factory',
			'WPML_TM_Emails_Settings_Factory',
			'WPML_TM_Jobs_Summary_Report_Hooks_Factory',
			\WPML\TM\Menu\TranslationServices\Resources::class,
			\WPML\TM\Menu\TranslationServices\ActivationAjaxFactory::class,
			\WPML\TM\Menu\TranslationServices\AuthenticationAjaxFactory::class,
			\WPML\TM\Menu\TranslationServices\Troubleshooting\RefreshServicesFactory::class,
			'WPML_TP_Lock_Notice_Factory',
			'WPML_TM_Parent_Filter_Ajax_Factory',
			'WPML_TM_Translation_Basket_Hooks_Factory',
			'WPML_TM_Admin_Menus_Factory',
			'WPML_TM_Privacy_Content_Factory',
			'WPML_TM_Serialized_Custom_Field_Package_Handler_Factory',
			'WPML_TM_MCS_Pagination_Ajax_Factory',
			'WPML_Translation_Jobs_Migration_Hooks_Factory',
			'WPML_TM_TS_Instructions_Hooks_Factory',
			'WPML_TM_Only_I_Language_Pairs',
			'WPML_TM_Post_Edit_TM_Editor_Select_Factory',
			'WPML_TM_Translation_Jobs_Fix_Summary_Factory',
			'WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID_Factory',
			\WPML\TM\Troubleshooting\SynchronizeSourceIdOfATEJobs\TriggerSynchronization::class,
			\WPML\TM\Troubleshooting\ResetPreferredTranslationService::class,
			'WPML_TM_Reset_Options_Filter_Factory',
			\WPML\TM\User\Hooks::class,
			\WPML\TM\Jobs\ExtraFieldDataInEditorFactory::class,
			\WPML\TM\ATE\Sitekey\Sync::class,
			\WPML\TM\ATE\Review\ReviewCompletedNotice::class,
			\WPML\TM\Settings\CustomFieldChangeDetector::class,
		];
		$action_filter_loader->load( $actions );

		$rest_actions = [
			'WPML_TM_REST_ATE_Jobs_Factory',
			'WPML_TM_REST_XLIFF_Factory',
			'WPML_TM_REST_AMS_Clients_Factory',
			'WPML_TM_REST_ATE_API_Factory',
			'WPML_TM_REST_Jobs_Factory',
			'WPML_TM_REST_TP_XLIFF_Factory',
			'WPML_TM_REST_Apply_TP_Translation_Factory',
			'WPML_TM_REST_Batch_Sync_Factory',
			\WPML\TM\REST\FactoryLoader::class,
			\WPML\TM\ATE\Factories\Proxy::class,
		];
		$action_filter_loader->load( $rest_actions );

		$ams_ate_actions = [
			'WPML_TM_AMS_Synchronize_Actions_Factory',
			'WPML_TM_AMS_Synchronize_Users_On_Access_Denied_Factory',
			'WPML_TM_ATE_Jobs_Store_Actions_Factory',
			'WPML_TM_ATE_Jobs_Actions_Factory',
			'WPML_TM_ATE_Job_Data_Fallback_Factory',
			'WPML_TM_ATE_Post_Edit_Actions_Factory',
			'WPML_TM_ATE_Translator_Message_Classic_Editor_Factory',
			'WPML_TM_Old_Editor_Factory',
			\WPML\TM\ATE\Log\Hooks::class,
			\WPML\TM\ATE\Hooks\ReturnedJobActionsFactory::class,
			ReportAjax::class,
			AteLockNotice::class,
			\WPML\TM\ATE\Loader::class,
			\WPML\TM\ATE\Review\ApplyJob::class,
			\WPML\TM\ATE\Review\StatusIcons::class,
			\WPML\TM\ATE\StatusIcons::class,
			\WPML\TM\Editor\ManualJobCreationErrorNotice::class
		];
		$action_filter_loader->load( $ams_ate_actions );

		$after_ate_actions = [
			\WPML\TranslateLinkTargets\Hooks::class,
		];
		$action_filter_loader->load( $after_ate_actions );
	}

	do_action( 'wpml_after_tm_loaded' );

	if ( $is_admin ) {
		// This filter is documented WPML Core in classes/support/class-wpml-support-info-ui.php.
		add_filter( 'wpml_support_info_blocks', 'wpml_tm_support_info' );
	}

	initialize_wpml_cache_factory();
}

add_action( 'wpml_loaded', 'wpml_tm_load', 10, 1 );

/**
 * Get support info.
 * This filter is documented WPML Core in classes/support/class-wpml-support-info-ui.php.
 *
 * @param array $blocks Support info blocks.
 *
 * @return array
 */
function wpml_tm_support_info( array $blocks ) {
	$support_info = new WPML_TM_Support_Info();

	$ui = new WPML_TM_Support_Info_Filter( $support_info );

	return $ui->filter_blocks( $blocks );
}


/**
 * Migration from ICL 2.0
 */
function wpml_tm_icl20_migration() {
	// @todo Remove `|| ( defined( 'WPML_TP_ICL_20_MIGRATION_OFF' ) && WPML_TP_ICL_20_MIGRATION_OFF )` after testing?
	if ( defined( 'WPML_TP_ICL_20_MIGRATION_OFF' ) && WPML_TP_ICL_20_MIGRATION_OFF ) {
		return;
	}

	global $sitepress;
	$loader = new WPML_TM_ICL20_Migration_Loader( $sitepress->get_wp_api(), new WPML_TM_ICL20_Migration_Factory() );
	$loader->run();
}

if ( ! empty( $GLOBALS['sitepress'] ) && is_admin() ) {
	add_action( 'wpml_tm_loaded', 'wpml_tm_icl20_migration' );
}

/**
 * WPML reset user options filter.
 *
 * @param array $options User options.
 *
 * @return array
 */
function wpml_tm_reset_user_options( array $options ) {
	$options[] = WPML_TM_Menus_Management::SKIP_TM_WIZARD_META_KEY;

	return $options;
}

if ( is_admin() ) {
	add_filter( 'wpml_reset_user_options', 'wpml_tm_reset_user_options' );
}

if ( is_admin() && !wpml_is_ajax() ) {
	CustomFieldChangeDetector::processNewFields();
}
