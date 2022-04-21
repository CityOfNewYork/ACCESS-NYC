<?php


namespace WPML\TM\ATE;


use WPML\API\Settings;
use WPML\DocPage;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\User;
use WPML\TM\ATE\AutoTranslate\Endpoint\AutoTranslate;
use WPML\TM\ATE\AutoTranslate\Endpoint\EnableATE;
use WPML\TM\ATE\AutoTranslate\Endpoint\CancelJobs;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetATEJobsToSync;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetCredits;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetStatus;
use WPML\TM\ATE\AutoTranslate\Endpoint\RefreshJobsStatus;
use WPML\TM\ATE\AutoTranslate\Endpoint\SyncLock;
use WPML\TM\ATE\Download\Queue;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Sync\Trigger;
use WPML\TM\WP\App\Resources;
use WPML\UIPage;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\pipe;
use WPML\LIB\WP\Hooks;
use WPML\Setup\Option;

class Loader implements \IWPML_Backend_Action {

	const JOB_ID_PLACEHOLDER = '###';

	public function add_hooks() {
		if ( wpml_is_ajax() ) {
			// Prevent loading this for ajax calls.
			// All tasks of this class are not relevant for ajax requests. Currently it's loaded by the root plugin.php
			// which do not separate between ajax and non-ajax calls and loads this whenever is_admin() is true.
			// Problem: ALL ajax calls return true for is_admin() - also on the frontend and for non logged-in users.
			// TODO: Remove once wpmltm-4351 is done.
			return;
		}

		if (
			\WPML_TM_ATE_Status::is_enabled_and_activated()
			|| Settings::pathOr( false, [ 'translation-management', 'doc_translation_method' ] ) === ICL_TM_TMETHOD_ATE
		) {
			StatusBar::add_hooks();

			Hooks::onAction( 'in_admin_header' )
			     ->then( [ self::class, 'showAteConsoleContainer' ] );
		}

		Hooks::onAction( 'wp_loaded' )
		     ->then( [ self::class, 'getData' ] )
		     ->then( Resources::enqueueApp( 'ate-jobs-sync' ) )
		     ->then( Fns::always( make( \WPML_TM_Scripts_Factory::class ) ) )
		     ->then( invoke( 'localize_script' )->with( 'wpml-ate-jobs-sync-ui' ) );

		Hooks::onFilter( 'wpml_tm_get_wpml_auto_translate_container' )
		     ->then( [ self::class, 'getWpmlAutoTranslateContainer' ] );
	}

	public static function getData() {
		$jobsToSync = Jobs::getJobsWithStatus( [
			ICL_TM_WAITING_FOR_TRANSLATOR,
			ICL_TM_IN_PROGRESS,
			ICL_TM_ATE_NEEDS_RETRY
		] );

		$ateTab = admin_url( UIPage::getTMATE() );

		return [
			'name' => 'ate_jobs_sync',
			'data' => [
				'endpoints'            => self::getEndpoints(),
				'urls'                 => self::getUrls( $ateTab ),
				'jobIdPlaceHolder'     => self::JOB_ID_PLACEHOLDER,
				'notices'              => StatusBar::getNotices(),
				'isTranslationManager' => User::getCurrent()->has_cap( \WPML_Manage_Translations_Role::CAPABILITY ),

				'jobsToSync'       => $jobsToSync,
				'totalJobsCount'   => Jobs::getTotal(),
				'needsReviewCount' => count( Jobs::getJobsWithStatus( [ ICL_TM_NEEDS_REVIEW ] ) ),

				'shouldTranslateEverything' => Option::shouldTranslateEverything() && ! TranslateEverything::isEverythingProcessed( true ),

				'isAutomaticTranslations' => Option::shouldTranslateEverything(),
				'isSyncRequired'          => self::isSyncRequired() || count( $jobsToSync ),
				'needsFetchCredit'        => Option::shouldTranslateEverything() && UIPage::isTMDashboard( $_GET ),

				'strings'     => self::getStrings(),
				'ateConsole'  => self::getAteData( Lst::pluck( 'ateJobId', $jobsToSync ) ),
				'isAteActive' => \WPML_TM_ATE_Status::is_enabled_and_activated(),
				'editorMode'  => Settings::pathOr( false, [ 'translation-management', 'doc_translation_method' ] )
			],
		];
	}

	/**
	 * @return bool
	 */
	private static function isSyncRequired() {
		return make( Trigger::class )->isSyncRequired();
	}

	/**
	 * @return string
	 */
	public static function getNotEnoughCreditPopup() {
		$isTranslationManager = User::getCurrent()->has_cap( \WPML_Manage_Translations_Role::CAPABILITY );

		$content = $isTranslationManager
			? __(
				"There is an issue with automatic translation that needs your attention.",
				'wpml-translation-management'
			)
			: __(
				" There is an issue with automatic translation that needs attention from a translation manager.",
				'wpml-translation-management'
			);

		$fix = __( 'Fix it to continue translating automatically', 'wpml-translation-management' );

		$primaryButton = $isTranslationManager
			? '<button class="wpml-antd-button wpml-antd-button-primary" onclick="CREDITS_ACTION">' . $fix . '</button>'
			: '';

		$translate = __( 'Translate content myself', 'wpml-translation-management' );

		$secondaryButton = UIPage::isTMDashboard( $_GET ) || ! $isTranslationManager
			? ''
			: '<button class="wpml-antd-button wpml-antd-button-secondary" onclick="window.location.href=\'TRANSLATE_LINK\'">' . $translate . '</button>';

		return '<div class="wpml-not-enough-credit-popup">' .
		       '<p>' . $content . '</p>' .
		       $primaryButton .
		       $secondaryButton .
		       '</div>';
	}

	public static function showAteConsoleContainer() {
		echo '<div id="wpml-ate-console-container"></div>';
	}

	public static function getWpmlAutoTranslateContainer() {
		return '<div id="wpml-auto-translate" style="display:none">
					<div class="content"></div>
					<div class="connect"></div>
				</div>';
	}

	private static function getAteData( $ateJobIds ) {
		$registration_data = make( \WPML_TM_AMS_API::class )->get_registration_data();

		return User::getCurrent()->has_cap( \WPML_Manage_Translations_Role::CAPABILITY )
			? [
				'host'         => make( \WPML_TM_ATE_AMS_Endpoints::class )->get_base_url( \WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ),
				'wpml_host'    => get_site_url(),
				'job_list'     => $ateJobIds,
				'widget_mode'  => 'issue_solving',
				'return_url'   => \WPML\TM\API\Jobs::getCurrentUrl(),
				'secret_key'   => Obj::prop( 'secret', $registration_data ),
				'shared_key'   => Obj::prop( 'shared', $registration_data ),
				'website_uuid' => make( \WPML_TM_ATE_Authentication::class )->get_site_id(),
				'ui_language'  => make( \SitePress::class )->get_user_admin_language( User::getCurrentId() ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'container'    => '#wpml-ate-console-container',
			]
			: false;
	}

	private static function getEndpoints() {
		return [
			'auto-translate'       => AutoTranslate::class,
			'translate-everything' => TranslateEverything::class,
			'getCredits'           => GetCredits::class,
			'enableATE'            => EnableATE::class,
			'getATEJobsToSync'     => GetATEJobsToSync::class,
			'syncLock'             => SyncLock::class,
		];
	}

	private static function getUrls( $ateTab ) {
		return [
			'editor'                    => \WPML_TM_Translation_Status_Display::get_link_for_existing_job( self::JOB_ID_PLACEHOLDER ),
			'ateams'                    => $ateTab,
			'automaticSettings'         => \admin_url( UIPage::getSettings() ),
			'translateAutomaticallyDoc' => DocPage::getTranslateAutomatically(),
			'ateConsole'                => make( \WPML_TM_ATE_AMS_Endpoints::class )
				                               ->get_base_url( \WPML_TM_ATE_AMS_Endpoints::SERVICE_AMS ) . '/mini_app/main.js',
			'translationQueue'          => \add_query_arg(
				[ 'status' => ICL_TM_NEEDS_REVIEW ],
				\admin_url( UIPage::getTranslationQueue() )
			),
			'currentUrl' => \WPML\TM\API\Jobs::getCurrentUrl(),
		];
	}

	private static function getStrings() {
		return [
			'tooltip'              => __(
				'Processing translation (could take a few minutes)',
				'wpml-translation-management'
			),
			'refreshing'           => __( 'Refreshing translation status', 'wpml-translation-management' ),
			'inProgress'           => __( 'Translation in progress', 'wpml-translation-management' ),
			'editTranslation'      => __( 'Edit translation', 'wpml-translation-management' ),
			'status'               => __( 'Processing translation', 'wpml-translation-management' ),
			'automaticTranslation' => __( 'This content is being automatically translated. If you want to do something different with it cancel translation jobs first.', 'wpml-translation-management' ),
			'notEnoughCredit'      => self::getNotEnoughCreditPopup(),
			'cancelled'            => __( 'Translation has been cancelled', 'wpml-translation-management' ),
		];
	}
}
