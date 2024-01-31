<?php

use WPML\TM\Editor\ClassicEditorActions;
use WPML\TM\Jobs\Query\CompositeQuery;
use WPML\TM\Jobs\Query\LimitQueryHelper;
use WPML\TM\Jobs\Query\OrderQueryHelper;
use WPML\TM\Jobs\Query\PackageQuery;
use WPML\TM\Jobs\Query\PostQuery;
use WPML\TM\Jobs\Query\QueryBuilder;
use WPML\TM\Jobs\Query\StringQuery;
use WPML\TM\Jobs\Query\StringsBatchQuery;
use WPML\FP\Obj;
use function WPML\Container\make;
use \WPML\Setup\Option as SetupOptions;
use WPML\TM\ATE\TranslateEverything\TranslatableData\Calculate;

if ( ! \WPML\Plugins::isTMActive() && ( ! wpml_is_setup_complete() || false !== SetupOptions::isTMAllowed() ) ) {

	/**
	 * @return WPML_TM_Element_Translations
	 */
	function wpml_tm_load_element_translations() {
		global $wpml_tm_element_translations, $wpdb, $wpml_post_translations, $wpml_term_translations;

		if ( ! isset( $wpml_tm_element_translations ) && defined( 'WPML_TM_PATH' ) ) {
			require_once WPML_TM_PATH . '/inc/core/wpml-tm-element-translations.class.php';
			$tm_records                   = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
			$wpml_tm_element_translations = new WPML_TM_Element_Translations( $tm_records );
			$wpml_tm_element_translations->init_hooks();
		}

		return $wpml_tm_element_translations;
	}

	function wpml_tm_load_status_display_filter() {
		global $wpml_tm_status_display_filter, $iclTranslationManagement, $sitepress, $wpdb;

		$blog_translators = wpml_tm_load_blog_translators();
		$tm_api           = new WPML_TM_API( $blog_translators, $iclTranslationManagement );
		$tm_api->init_hooks();
		if ( ! isset( $wpml_tm_status_display_filter ) ) {
			$status_helper                 = wpml_get_post_status_helper();
			$job_factory                   = wpml_tm_load_job_factory();
			$wpml_tm_status_display_filter = new WPML_TM_Translation_Status_Display(
				$wpdb,
				$sitepress,
				$status_helper,
				$job_factory,
				$tm_api
			);
		}

		$wpml_tm_status_display_filter->init();
	}

	/**
	 * @depecated since WPML 4.5.0
	 *
	 * @return \WPML_TM_Page_Builders_Hooks
	 */
	function wpml_tm_page_builders_hooks() {
		static $page_builder_hooks;
		if ( ! $page_builder_hooks ) {
			global $sitepress;
			$page_builder_hooks = new WPML_TM_Page_Builders_Hooks( null, $sitepress );
		}

		return $page_builder_hooks;
	}

	/**
	 * @return \WPML_Custom_XML_Factory
	 */
	function wpml_tm_custom_xml_factory() {
		static $tm_custom_xml_factory;
		if ( ! $tm_custom_xml_factory ) {
			$tm_custom_xml_factory = new WPML_Custom_XML_Factory();
		}

		return $tm_custom_xml_factory;
	}

	/**
	 * @return \WPML_Custom_XML_UI_Hooks
	 */
	function wpml_tm_custom_xml_ui_hooks() {
		static $tm_custom_xml_ui_hooks;
		if ( ! $tm_custom_xml_ui_hooks ) {
			global $sitepress;
			$factory = wpml_tm_custom_xml_factory();
			if ( $factory ) {
				$tm_custom_xml_ui_hooks = new WPML_Custom_XML_UI_Hooks( $factory->create_resources( $sitepress->get_wp_api() ) );
			}
		}

		return $tm_custom_xml_ui_hooks;
	}

	/**
	 * @return \WPML_UI_Screen_Options_Factory
	 */
	function wpml_ui_screen_options_factory() {
		static $screen_options_factory;
		if ( ! $screen_options_factory ) {
			global $sitepress;
			$screen_options_factory = new WPML_UI_Screen_Options_Factory( $sitepress );
		}

		return $screen_options_factory;
	}

	/**
	 * @return \WPML_TM_Loader
	 */
	function wpml_tm_loader() {
		static $tm_loader;
		if ( ! $tm_loader ) {
			$tm_loader = new WPML_TM_Loader();
		}

		return $tm_loader;
	}

	/**
	 * @return \WPML_TP_Translator
	 */
	function wpml_tm_translator() {
		static $tm_translator;
		if ( ! $tm_translator ) {
			$tm_translator = new WPML_TP_Translator();
		}

		return $tm_translator;
	}

	/**
	 * It returns a single instance of \WPML_Translation_Management.
	 *
	 * @return \WPML_Translation_Management
	 */
	function wpml_translation_management() {
		global $WPML_Translation_Management;
		if ( ! $WPML_Translation_Management ) {
			global $sitepress;
			$WPML_Translation_Management = new WPML_Translation_Management( $sitepress, wpml_tm_loader(), wpml_load_core_tm(), wpml_tm_translator() );
		}

		return $WPML_Translation_Management;
	}

	/**
	 * @return \WPML_Translation_Basket
	 */
	function wpml_translation_basket() {
		static $translation_basket;
		if ( ! $translation_basket ) {
			global $wpdb;
			$translation_basket = new WPML_Translation_Basket( $wpdb );
		}

		return $translation_basket;
	}

	/**
	 * @return \WPML_TM_Translate_Independently
	 */
	function wpml_tm_translate_independently() {
		static $translate_independently;
		if ( ! $translate_independently ) {
			global $sitepress;

			$translate_independently = new WPML_TM_Translate_Independently( wpml_load_core_tm(), wpml_translation_basket(), $sitepress );
		}

		return $translate_independently;
	}

	/**
	 * @return WPML_Translation_Proxy_Basket_Networking
	 */
	function wpml_tm_load_basket_networking() {
		global $iclTranslationManagement, $wpdb;

		if ( ! defined( 'WPML_TM_PATH' ) ) {
			return null;
		}

		require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-translationproxy-basket-networking.class.php';

		$basket = new WPML_Translation_Basket( $wpdb );

		return new WPML_Translation_Proxy_Basket_Networking( $basket, $iclTranslationManagement );
	}

	/**
	 * @return WPML_Translation_Proxy_Networking
	 */
	function wpml_tm_load_tp_networking() {
		global $wpml_tm_tp_networking;

		if ( ! isset( $wpml_tm_tp_networking ) ) {
			$tp_lock_factory       = new WPML_TP_Lock_Factory();
			$wpml_tm_tp_networking = new WPML_Translation_Proxy_Networking( new WP_Http(), $tp_lock_factory->create() );
		}

		return $wpml_tm_tp_networking;
	}

	/**
	 * @return WPML_TM_Blog_Translators
	 */
	function wpml_tm_load_blog_translators() {
		global $wpdb, $sitepress, $wpml_post_translations, $wpml_term_translations, $wpml_cache_factory;
		static $instance;

		if ( ! $instance ) {
			$tm_records         = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
			$translator_records = new WPML_Translator_Records( $wpdb, new WPML_WP_User_Query_Factory(), wp_roles() );

			$instance = new WPML_TM_Blog_Translators( $sitepress, $tm_records, $translator_records, $wpml_cache_factory );
		}

		return $instance;
	}

	/**
	 * @return WPML_TM_Translators_Dropdown
	 */
	function wpml_tm_get_translators_dropdown() {
		static $instance;

		if ( ! $instance ) {
			$instance = new WPML_TM_Translators_Dropdown( wpml_tm_load_blog_translators() );
		}

		return $instance;
	}

	/**
	 * @return WPML_TM_Mail_Notification
	 */
	function wpml_tm_init_mail_notifications() {
		global $wpml_tm_mailer, $sitepress, $wpdb, $iclTranslationManagement, $wp_api;

		if ( null === $wp_api ) {
			$wp_api = new WPML_WP_API();
		}

		if ( is_admin() && defined( 'WPML_TM_PATH' ) ) {
			$blog_translators            = wpml_tm_load_blog_translators();
			$email_twig_factory          = new WPML_TM_Email_Twig_Template_Factory();
			$batch_report                = new WPML_TM_Batch_Report( $blog_translators );
			$batch_report_email_template = new WPML_TM_Email_Jobs_Summary_View(
				$email_twig_factory->create(),
				$blog_translators,
				$sitepress
			);
			$batch_report_email_builder  = new WPML_TM_Batch_Report_Email_Builder(
				$batch_report,
				$batch_report_email_template
			);
			$batch_report_email_process  = new WPML_TM_Batch_Report_Email_Process(
				$batch_report,
				$batch_report_email_builder
			);
			$batch_report_hooks          = new WPML_TM_Batch_Report_Hooks( $batch_report, $batch_report_email_process );
			$batch_report_hooks->add_hooks();

			$wpml_tm_unsent_jobs = new WPML_TM_Unsent_Jobs( $blog_translators, $sitepress );
			$wpml_tm_unsent_jobs->add_hooks();

			$wpml_tm_unsent_jobs_notice       = new WPML_TM_Unsent_Jobs_Notice( $wp_api );
			$wpml_tm_unsent_jobs_notice_hooks = new WPML_TM_Unsent_Jobs_Notice_Hooks(
				$wpml_tm_unsent_jobs_notice,
				$wp_api,
				WPML_Notices::DISMISSED_OPTION_KEY
			);
			$wpml_tm_unsent_jobs_notice_hooks->add_hooks();

			$user_jobs_notification_settings = new WPML_User_Jobs_Notification_Settings();
			$user_jobs_notification_settings->add_hooks();

			$email_twig_factory    = new WPML_Twig_Template_Loader( array( WPML_TM_PATH . '/templates/user-profile/' ) );
			$notification_template = new WPML_User_Jobs_Notification_Settings_Template( $email_twig_factory->get_template() );

			$user_jobs_notification_settings_render = new WPML_User_Jobs_Notification_Settings_Render( $notification_template );
			$user_jobs_notification_settings_render->add_hooks();
		}

		if ( ! isset( $wpml_tm_mailer ) ) {
			$iclTranslationManagement = $iclTranslationManagement ? $iclTranslationManagement : wpml_load_core_tm();
			if ( empty( $iclTranslationManagement->settings ) ) {
				$iclTranslationManagement->init();
			}
			$settings = isset( $iclTranslationManagement->settings['notification'] ) ? $iclTranslationManagement->settings['notification'] : array();

			$email_twig_factory      = new WPML_TM_Email_Twig_Template_Factory();
			$email_notification_view = new WPML_TM_Email_Notification_View( $email_twig_factory->create() );

			$has_active_remote_service = TranslationProxy::is_current_service_active_and_authenticated();

			$wpml_tm_mailer = new WPML_TM_Mail_Notification(
				$sitepress,
				$wpdb,
				wpml_tm_load_job_factory(),
				$email_notification_view,
				$settings,
				$has_active_remote_service
			);
		}
		$wpml_tm_mailer->init();

		return $wpml_tm_mailer;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return WPML_Dashboard_Ajax
	 */
	function wpml_tm_load_tm_dashboard_ajax() {
		global $wpml_tm_dashboard_ajax, $sitepress;

		if ( ! isset( $wpml_tm_dashboard_ajax ) && defined( 'WPML_TM_PATH' ) ) {
			require_once WPML_TM_PATH . '/menu/dashboard/wpml-tm-dashboard-ajax.class.php';
			$wpml_tm_dashboard_ajax = new WPML_Dashboard_Ajax( );

			if ( defined( 'OTG_TRANSLATION_PROXY_URL' ) && defined( 'ICL_SITEPRESS_VERSION' ) ) {
				$wpml_tp_api = wpml_tm_get_tp_project_api();

				$wpml_tp_api_ajax = new WPML_TP_Refresh_Language_Pairs( $wpml_tp_api );
				$wpml_tp_api_ajax->add_hooks();

				$sync_jobs_ajax_handler = new WPML_TP_Sync_Ajax_Handler(
					wpml_tm_get_tp_sync_jobs(),
					new WPML_TM_Last_Picked_Up( $sitepress )
				);
				$sync_jobs_ajax_handler->add_hooks();
			}
		}

		return $wpml_tm_dashboard_ajax;
	}

	function wpml_tm_load_and_intialize_dashboard_ajax() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			if ( defined( 'DOING_AJAX' ) ) {
				wpml_tm_load_tm_dashboard_ajax();
			} elseif (
				defined( 'WPML_TM_FOLDER' ) && is_admin() && isset( $_GET['page'] ) && WPML_TM_FOLDER . '/menu/main.php' === $_GET['page'] && ( ! isset( $_GET['sm'] ) || $_GET['sm'] === 'dashboard' )
			) {
				$wpml_tm_dashboard_ajax = wpml_tm_load_tm_dashboard_ajax();
				add_action( 'wpml_tm_scripts_enqueued', array( $wpml_tm_dashboard_ajax, 'enqueue_js' ) );
			}
		}
	}

	add_action( 'plugins_loaded', 'wpml_tm_load_and_intialize_dashboard_ajax' );

	/**
	 * It returns a single instance of the class.
	 *
	 * @return WPML_Translation_Job_Factory
	 */
	function wpml_tm_load_job_factory() {
		global $wpml_translation_job_factory, $wpdb, $wpml_post_translations, $wpml_term_translations;

		if ( ! $wpml_translation_job_factory ) {
			$tm_records                   = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
			$wpml_translation_job_factory = new WPML_Translation_Job_Factory( $tm_records );
			$wpml_translation_job_factory->init_hooks();
		}

		return $wpml_translation_job_factory;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return WPML_TM_XLIFF_Factory
	 */
	function wpml_tm_xliff_factory() {
		static $xliff_factory;

		if ( ! $xliff_factory ) {
			$xliff_factory = new WPML_TM_XLIFF_Factory();
		}

		return $xliff_factory;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return WPML_TM_XLIFF_Shortcodes
	 */
	function wpml_tm_xliff_shortcodes() {
		static $xliff_shortcodes;

		if ( ! $xliff_shortcodes ) {
			$xliff_shortcodes = new WPML_TM_XLIFF_Shortcodes();
		}

		return $xliff_shortcodes;
	}

	/**
	 * It returns an instance of the class.
	 *
	 * @return \WPML_TM_Old_Jobs_Editor
	 */
	function wpml_tm_load_old_jobs_editor() {
		static $instance;

		if ( ! $instance ) {
			$instance = new WPML_TM_Old_Jobs_Editor( wpml_tm_load_job_factory() );
		}

		return $instance;
	}

	function tm_after_load() {
		global $wpml_tm_translation_status, $wpdb, $wpml_post_translations, $wpml_term_translations;

		if ( ! isset( $wpml_tm_translation_status ) && defined( 'WPML_TM_PATH' ) ) {
			require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy.class.php';
			require_once WPML_TM_PATH . '/inc/ajax.php';

			( new ClassicEditorActions() )->addHooks();

			wpml_tm_load_job_factory();
			wpml_tm_init_mail_notifications();
			wpml_tm_load_element_translations();
			$wpml_tm_translation_status = make( WPML_TM_Translation_Status::class );
			$wpml_tm_translation_status->init();
			add_action( 'wpml_pre_status_icon_display', 'wpml_tm_load_status_display_filter' );
			require_once WPML_TM_PATH . '/inc/wpml-private-actions-tm.php';
		}
	}

	/**
	 * It returns an instance of the class.
	 *
	 * @return WPML_TM_Records
	 */
	function wpml_tm_get_records() {
		global $wpdb, $wpml_post_translations, $wpml_term_translations;

		return new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
	}

	/**
	 * It returns an instance of the class.
	 *
	 * @return WPML_TM_Xliff_Frontend
	 */
	function setup_xliff_frontend() {
		global $xliff_frontend;

		$xliff_factory  = new WPML_TM_XLIFF_Factory();
		$xliff_frontend = $xliff_factory->create_frontend();

		add_action( 'init', array( $xliff_frontend, 'init' ), $xliff_frontend->get_init_priority() );

		return $xliff_frontend;
	}

	/**
	 * It returns an instance of the class.
	 *
	 * @param int $job_id The ID of the job.
	 * @param bool $applyTranslationMemoryForCompletedJobs
	 *
	 * @return WPML_TM_ATE_Models_Job_Create
	 */
	function wpml_tm_create_ATE_job_creation_model( $job_id, $applyTranslationMemoryForCompletedJobs = true ) {
		$job_factory     = wpml_tm_load_job_factory();
		$translation_job = $job_factory->get_translation_job( $job_id, false, 0, true );

		$rid = \WPML\TM\API\Job\Map::fromJobId( $job_id );

		$job            = new WPML_TM_ATE_Models_Job_Create();
		$job->id		= $job_id;
		$job->source_id = $rid;

		$previousStatus = \WPML_TM_ICL_Translation_Status::makeByRid( $rid )->previous();
		if ( $previousStatus->map( Obj::prop( 'status' ) )->getOrElse( null ) === (string) ICL_TM_ATE_CANCELLED ) {
			wpml_tm_load_job_factory()->update_job_data( $job_id, array( 'editor' => WPML_TM_Editors::ATE ) );
			$job->existing_ate_id = make( \WPML\TM\ATE\JobRecords::class )->get_ate_job_id( $job_id );
		} else {
			/**
			 * We have to use the previous state because in this place the job has already changed its status from COMPLETED to IN PROGRESS.
			 */
			$apply_memory = (bool) $previousStatus->map( function ( $job ) use ( $applyTranslationMemoryForCompletedJobs ) {
				$result = (int) Obj::prop( 'status', $job ) === ICL_TM_COMPLETE && ! Obj::prop( 'needs_update', $job ) ? $applyTranslationMemoryForCompletedJobs : true;

				// I have to cast it to int because if I return bool FALSE, Maybe internal mechanism treats it as nullable and default value from `getOrElse` is returned instead of $result.
				return (int) $result;
			} )->getOrElse( $applyTranslationMemoryForCompletedJobs );

			$job->source_language->code = $translation_job->get_source_language_code();
			$job->source_language->name = $translation_job->get_source_language_code( true );
			$job->target_language->code = $translation_job->get_language_code();
			$job->target_language->name = $translation_job->get_language_code( true );
			$job->deadline              = strtotime( $translation_job->get_deadline_date() );
			$job->apply_memory          = $apply_memory;

			/*
			 * wpmldev-1840
			 *
			 * With wpmldev-1730 WPML estimates the credits, which the site will
			 * require by fetching post content, title and excerpt.
			 * In the future this estimation should happen on ATE, but for that
			 * they need to get the WPML calculated chars per job to compare
			 * with the real costs for the translation.
			 * Once ATE is providing the calculation and does no longer need
			 * the `wpml_chars_count` parameter, the following block
			 * until "END" can be deleted.
			 *
			 * Also the property "wpml_chars_count" can be removed from
			 * ./classes/ATE/models/class-wpml-tm-ate-models-job-create.php
			 */
			$calculate = new Calculate();
			$fields    = $translation_job->get_original_fields();
			foreach ( $fields as $key => $value ) {
				if (
					! empty( $value ) &&
					in_array( $key, [ 'title', 'body', 'excerpt' ], true )
				) {
					$job->wpml_chars_count += $calculate->chars( $value );
				}
			}
			/* END */

			$job->permalink = '#';
			if ( 'Post' === $translation_job->get_type() ) {
				$job->permalink = get_permalink( $translation_job->get_original_element_id() );
			}

			$job->notify_enabled = true;
			$job->notify_url     = \WPML\TM\ATE\REST\PublicReceive::get_receive_ate_job_url( $job_id );

			$job->site_identifier = wpml_get_site_id( WPML_TM_ATE::SITE_ID_SCOPE );

			$encoded_xliff = base64_encode( wpml_tm_get_job_xliff( $job_id, $apply_memory ) );

			$job->file->type = 'data:application/x-xliff;base64';
			$job->file->name = $translation_job->get_title();

			$job->file->content = $encoded_xliff;
		}

		return $job;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @param int   $job_id The ID of the job.
	 * @param bool  $apply_memory
	 *
	 * @return string
	 */
	function wpml_tm_get_job_xliff( $job_id, $apply_memory = true ) {
		static $xliff_writer;

		if ( ! $xliff_writer ) {
			$job_factory  = wpml_tm_load_job_factory();
			$xliff_writer = new WPML_TM_Xliff_Writer( $job_factory );
		}

		return $xliff_writer->generate_job_xliff( $job_id, $apply_memory );
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_Rest
	 */
	function wpml_tm_get_wpml_rest() {
		static $wpml_rest;

		if ( ! $wpml_rest ) {
			$http      = new WP_Http();
			$wpml_rest = new WPML_Rest( $http );
		}

		return $wpml_rest;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_API_Client
	 */
	function wpml_tm_get_tp_api_client() {
		static $client;

		if ( ! $client ) {
			$client = new WPML_TP_API_Client(
				OTG_TRANSLATION_PROXY_URL,
				new WP_Http(),
				new WPML_TP_Lock( new WPML_WP_API() ),
				new WPML_TP_HTTP_Request_Filter()
			);
		}

		return $client;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_Project
	 */
	function wpml_tm_get_tp_project() {
		static $project;

		if ( ! $project ) {
			global $sitepress;

			$translation_service  = $sitepress->get_setting( 'translation_service' );
			$translation_projects = $sitepress->get_setting( 'icl_translation_projects' );
			$project              = new WPML_TP_Project( $translation_service, $translation_projects );
		}

		return $project;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_Jobs_API
	 */
	function wpml_tm_get_tp_jobs_api() {
		static $api;

		if ( ! $api ) {
			$api = new WPML_TP_Jobs_API(
				wpml_tm_get_tp_api_client(),
				wpml_tm_get_tp_project(),
				new WPML_TM_Log()
			);
		}

		return $api;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_Project_API
	 */
	function wpml_tm_get_tp_project_api() {
		static $api;

		if ( ! $api ) {
			$api = new WPML_TP_Project_API(
				wpml_tm_get_tp_api_client(),
				wpml_tm_get_tp_project(),
				new WPML_TM_Log()
			);
		}

		return $api;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_XLIFF_API
	 */
	function wpml_tm_get_tp_xliff_api() {
		static $api;

		if ( ! $api ) {
			$api = new WPML_TP_XLIFF_API(
				wpml_tm_get_tp_api_client(),
				wpml_tm_get_tp_project(),
				new WPML_TM_Log(),
				new WPML_TP_Xliff_Parser(
					new \WPML_TM_Validate_HTML()
				),
				wpml_tm_get_tp_jobs_api()
			);
		}

		return $api;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @param bool $forceReload
	 * @param bool $loadObsoleteStringQuery
	 * @param bool $dontCache
	 *
	 * @return \WPML_TM_Jobs_Repository
	 */
	function wpml_tm_get_jobs_repository( $forceReload = false, $loadObsoleteStringQuery = true, $dontCache = false ) {
		static $repository;

		if ( ! $repository || $forceReload ) {
			global $wpdb;

			$limit_helper = new LimitQueryHelper();
			$order_helper = new OrderQueryHelper();

			$subqueries = array(
				new PostQuery( $wpdb, new QueryBuilder( $limit_helper, $order_helper ) ),
			);
			if ( defined( 'WPML_ST_VERSION' ) && get_option( 'wpml-package-translation-db-updates-run' ) ) {
				$subqueries[] = new PackageQuery(
					$wpdb,
					new QueryBuilder( $limit_helper, $order_helper )
				);

				if ( $loadObsoleteStringQuery ) {
					$subqueries[] = new StringQuery(
						$wpdb,
						new QueryBuilder( $limit_helper, $order_helper )
					);
				}
				$subqueries[] = new StringsBatchQuery(
					$wpdb,
					new QueryBuilder( $limit_helper, $order_helper )
				);
			}

			$result = new WPML_TM_Jobs_Repository(
				$wpdb,
				new CompositeQuery(
					$subqueries,
					$limit_helper,
					$order_helper
				),
				new WPML_TM_Job_Elements_Repository( $wpdb )
			);

			if ( $dontCache ) {
				return $result;
			} else {
				$repository = $result;
			}
		}

		return $repository;
	}

	function wpml_tm_reload_jobs_repository() {
		wpml_tm_get_jobs_repository( true );
	}

	/**
	 * Reloading Jobs Repository when WPML String Translation finishes setup.
	 */
	add_action( 'wpml_register_string_packages', 'wpml_tm_reload_jobs_repository' );

	/**
	 * It returns an instance of the class.
	 *
	 * @return WPML_TM_ATE_Job_Repository
	 */
	function wpml_tm_get_ate_jobs_repository() {
		static $instance;

		if ( ! $instance ) {
			return new WPML_TM_ATE_Job_Repository( wpml_tm_get_jobs_repository() );
		}

		return $instance;
	}

	/**
	 * @return \WPML\TM\ATE\JobRecords
	 */
	function wpml_tm_get_ate_job_records() {
		global $wpdb;
		static $instance;

		if ( ! $instance ) {
			$instance = new WPML\TM\ATE\JobRecords( $wpdb );
		}

		return $instance;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_Sync_Jobs
	 */
	function wpml_tm_get_tp_sync_jobs() {
		static $sync_jobs;

		if ( ! $sync_jobs ) {
			global $wpdb, $sitepress;

			$sync_jobs = new WPML_TP_Sync_Jobs(
				new WPML_TM_Sync_Jobs_Status( wpml_tm_get_jobs_repository(), wpml_tm_get_tp_jobs_api() ),
				new WPML_TM_Sync_Jobs_Revision( wpml_tm_get_jobs_repository(), wpml_tm_get_tp_jobs_api() ),
				new WPML_TP_Sync_Update_Job( $wpdb, $sitepress )
			);
		}

		return $sync_jobs;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TP_Translations_Repository
	 */
	function wpml_tm_get_tp_translations_repository() {
		static $repository;

		if ( ! $repository ) {
			$repository = new WPML_TP_Translations_Repository(
				wpml_tm_get_tp_xliff_api(),
				wpml_tm_get_jobs_repository()
			);
		}

		return $repository;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_WP_User_Query_Factory
	 */
	function wpml_tm_get_wp_user_query_factory() {
		static $wp_user_query_factory;

		if ( ! $wp_user_query_factory ) {
			$wp_user_query_factory = new WPML_WP_User_Query_Factory();
		}

		return $wp_user_query_factory;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_WP_User_Factory
	 */
	function wpml_tm_get_wp_user_factory() {
		static $wp_user_factory;

		if ( ! $wp_user_factory ) {
			$wp_user_factory = new WPML_WP_User_Factory();
		}

		return $wp_user_factory;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TM_Email_Twig_Template_Factory
	 */
	function wpml_tm_get_email_twig_template_factory() {
		static $email_twig_template_factory;

		if ( ! $email_twig_template_factory ) {
			$email_twig_template_factory = new WPML_TM_Email_Twig_Template_Factory();
		}

		return $email_twig_template_factory;
	}

	/**
	 * It returns a single instance of the class.
	 *
	 * @return \WPML_TM_AMS_ATE_Factories
	 */
	function wpml_tm_ams_ate_factories() {
		static $tm_ams_ate_factories;

		if ( ! $tm_ams_ate_factories ) {
			$tm_ams_ate_factories = new WPML_TM_AMS_ATE_Factories();
		}

		return $tm_ams_ate_factories;
	}

	/**
	 * @return string
	 * @throws \Auryn\InjectionException
	 */
	function wpml_tm_get_ams_ate_console_url() {
		/** @var WPML_TM_Admin_Sections $admin_sections */
		$admin_sections = WPML\Container\make( 'WPML_TM_Admin_Sections' );

		return $admin_sections->get_item_url( WPML_TM_AMS_ATE_Console_Section::SLUG );
	}

	/**
	 * @param \WPML\TM\ATE\Log\Entry $entry
	 * @param bool $avoidDuplication
	 *
	 * @throws \WPML\Auryn\InjectionException
	 */
	function wpml_tm_ate_ams_log( WPML\TM\ATE\Log\Entry $entry, $avoidDuplication = false ) {
		make( WPML\TM\ATE\Log\Storage::class )->add( $entry, $avoidDuplication );
	}

	/**
	 * @param \WPML\TM\ATE\Log\Entry $entry
	 *
	 * @throws \WPML\Auryn\InjectionException
	 */
	function wpml_tm_ate_ams_log_remove( WPML\TM\ATE\Log\Entry $entry ) {
		make( WPML\TM\ATE\Log\Storage::class )->remove( $entry );
	}

	/**
	 * @param string $original
	 * @param string $translation
	 * @param bool $finished_state
	 *
	 * @return WPML_TM_Translated_Field
	 */
	function wpml_tm_create_translated_field( $original, $translation, $finished_state ) {
		return new WPML_TM_Translated_Field( $original, $translation, $finished_state );
	}

	/**
	 * @param int $post_id
	 * @param \WP_Post $post
	 * @param bool $force_set_status
	 */
	function wpml_tm_save_post( $post_id, $post, $force_set_status = false ) {
		global $wpdb, $wpml_post_translations, $wpml_term_translations;

		if ( false === $force_set_status && get_post_meta( $post_id, '_icl_lang_duplicate_of', true ) ) {
			$force_set_status = ICL_TM_DUPLICATE;
		}

		$action_helper    = new WPML_TM_Action_Helper();
		$blog_translators = wpml_tm_load_blog_translators();
		$tm_records       = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$save_post_action = new WPML_TM_Post_Actions( $action_helper, $blog_translators, $tm_records );
		if ( 'revision' === $post->post_type || 'auto-draft' === $post->post_status || isset( $_POST['autosave'] ) ) {
			return;
		}
		$save_post_action->save_post_actions( $post_id, $post, $force_set_status );
	}

	add_action( 'wpml_tm_save_post', 'wpml_tm_save_post', 10, 3 );

}
