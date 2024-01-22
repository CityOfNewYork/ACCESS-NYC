<?php

namespace WPML\TM\ATE;

use WPML\Element\API\Languages;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\API\Settings;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\Core\BackgroundTask\Repository\BackgroundTaskRepository;
use WPML\DocPage;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\User;
use WPML\Posts\UntranslatedCount;
use WPML\Setup\Option;
use WPML\TM\ATE\AutoTranslate\Endpoint\AutoTranslate;
use WPML\TM\ATE\AutoTranslate\Endpoint\CancelJobs;
use WPML\TM\ATE\AutoTranslate\Endpoint\CountJobsInProgress;
use WPML\TM\ATE\AutoTranslate\Endpoint\EnableATE;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetATEJobsToSync;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetCredits;
use WPML\TM\ATE\AutoTranslate\Endpoint\GetStatus;
use WPML\TM\ATE\AutoTranslate\Endpoint\RefreshJobsStatus;
use WPML\TM\ATE\AutoTranslate\Endpoint\SyncLock;
use WPML\TM\ATE\AutoTranslate\Endpoint\Languages as EndpointLanguages;
use WPML\TM\ATE\Download\Queue;
use WPML\TM\ATE\LanguageMapping\InvalidateCacheEndpoint;
use WPML\TM\ATE\Retranslation\Endpoint as RetranslationEndpoint;
use WPML\TM\ATE\Sync\Trigger;
use WPML\TM\ATE\TranslateEverything\Pause\View as PauseTranslateEverything;
use WPML\Core\WP\App\Resources;
use WPML\UIPage;
use WPML\TM\ATE\Retranslation\Scheduler;
use function WPML\Container\make;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class Loader implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const JOB_ID_PLACEHOLDER = '###';

	/** @var BackgroundTaskRepository */
	private $backgroundTaskRepository;

	/**
	 * @param BackgroundTaskRepository $backgroundTaskRepository
	 */
	public function __construct( BackgroundTaskRepository $backgroundTaskRepository ) {
		$this->backgroundTaskRepository = $backgroundTaskRepository;
	}

	public function add_hooks() {
		if ( wpml_is_ajax() ) {
			// Prevent loading this for ajax calls.
			// All tasks of this class are not relevant for ajax requests. Currently it's loaded by the root plugin.php
			// which do not separate between ajax and non-ajax calls and loads this whenever is_admin() is true.
			// Problem: ALL ajax calls return true for is_admin() - also on the frontend and for non logged-in users.
			// TODO: Remove once wpmltm-4351 is done.
			return;
		}

		if ( UIPage::isTMJobs( $_GET ) ) {
			return;
		}

		$displayBackgroundTasks = $this->backgroundTaskRepository->getCountRunnableTasks() > 0;

		$maybeLoadStatusBarAndATEConsole = Fns::tap( function ( $data ) use ( $displayBackgroundTasks ) {
			if (
				\WPML_TM_ATE_Status::is_enabled_and_activated()
				|| Settings::pathOr( false, [ 'translation-management', 'doc_translation_method' ] ) === ICL_TM_TMETHOD_ATE
				|| $displayBackgroundTasks
			) {
				StatusBar::add_hooks( $data['data']['hasAutomaticJobsInProgress'], $data['data']['needsReviewCount'], $displayBackgroundTasks  );

				Hooks::onAction( 'in_admin_header' )
				     ->then( [ self::class, 'showAteConsoleContainer' ] );
			}
		} );

		Hooks::onAction( 'wp_loaded' )
		     ->then( [ self::class, 'getData' ] )
		     ->then( $maybeLoadStatusBarAndATEConsole )
		     ->then( Resources::enqueueApp( 'ate-jobs-sync' ) )
		     ->then( Fns::always( make( \WPML_TM_Scripts_Factory::class ) ) )
		     ->then( invoke( 'localize_script' )->with( 'wpml-ate-jobs-sync-ui' ) );

		Hooks::onFilter( 'wpml_tm_get_wpml_auto_translate_container' )
		     ->then( [ self::class, 'getWpmlAutoTranslateContainer' ] );
	}

	public static function getData() {
		$jobsToSync = Jobs::getJobsToSync();

		$anyJobsExist = Jobs::isThereJob();

		$ateTab = admin_url( UIPage::getTMATE() );

		$isAteActive = \WPML_TM_ATE_Status::is_enabled_and_activated();

		$defaultLanguage = Languages::getDefaultCode();
		$getLanguages    = pipe(
			Languages::class . '::getActive',
			CachedLanguageMappings::withCanBeTranslatedAutomatically(),
			CachedLanguageMappings::withMapping(),
			Fns::map( Obj::over( Obj::lensProp( 'mapping' ), Obj::prop( 'targetCode' ) ) ),
			Fns::map(
				Obj::addProp(
					'is_default',
					Relation::propEq( 'code', $defaultLanguage )
				)
			),
			Obj::values()
		);

		/** @var Scheduler */
		$scheduler = make( Scheduler::class );

		return [
			'name' => 'ate_jobs_sync',
			'data' => [
				'endpoints'                   => self::getEndpoints(),
				'urls'                        => self::getUrls( $ateTab ),
				'jobIdPlaceHolder'            => self::JOB_ID_PLACEHOLDER,
				'languages'                   => $isAteActive ? $getLanguages() : [],
				'isTranslationManager'        => User::canManageTranslations(),

				'jobsToSync'                  => $jobsToSync,
				'anyJobsExist'                => $anyJobsExist,
				'totalJobsCount'              => Jobs::getTotal(),
				'needsReviewCount'            => count( Jobs::getJobsWithStatus( [ ICL_TM_NEEDS_REVIEW ] ) ),

				'shouldTranslateEverything'   =>
					! Option::isPausedTranslateEverything()
					&& Option::shouldTranslateEverything()
					&& ! TranslateEverything::isEverythingProcessed( true ),
				'isPausedTranslateEverything' => Option::isPausedTranslateEverything() ? 1 : 0,

				'isAutomaticTranslations'     => Option::shouldTranslateEverything(),
				'hasAutomaticJobsInProgress'  => Logic::isNotEmpty( Fns::filter( Obj::prop( 'automatic' ), $jobsToSync ) ),
				'isSyncRequired'              => count( $jobsToSync ),

				'strings'                     => self::getStrings(),
				'ateConsole'                  => self::getAteData( Lst::pluck( 'ateJobId', $jobsToSync ) ),
				'isAteActive'                 => $isAteActive,
				'editorMode'                  => Settings::pathOr( false, [ 'translation-management', 'doc_translation_method' ] ),
				'shouldCheckForRetranslation' => $scheduler->shouldRun(),
				'ateCallbacks' => [], // Should be used to add any needed ATE callbacks in JS side, refer to 'src/js/ate/retranslation/index.js' for example

				'settings' => [
					'numberOfParallelDownloads' => defined('WPML_ATE_MAX_PARALLEL_DOWNLOADS') ? WPML_ATE_MAX_PARALLEL_DOWNLOADS : 10,
				],
			],
		];
	}

	/**
	 * @return string
	 */
	public static function getNotEnoughCreditPopup() {
		$isTranslationManager = User::canManageTranslations();

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
		if ( User::canManageTranslations() ) {
			/** @var NoCreditPopup $noCreditPopup */
			$noCreditPopup = make( NoCreditPopup::class );

			return $noCreditPopup->getData( $ateJobIds );
		}

		return false;
	}

	private static function getEndpoints() {
		return [
			'auto-translate'               => AutoTranslate::class,
			'translate-everything'         => TranslateEverything::class,
			'getCredits'                   => GetCredits::class,
			'enableATE'                    => EnableATE::class,
			'getATEJobsToSync'             => GetATEJobsToSync::class,
			'syncLock'                     => SyncLock::class,
			'pauseTranslateEverything'     => PauseTranslateEverything::class,
			'untranslatedCount'            => UntranslatedCount::class,
			'countAutomaticJobsInProgress' => CountJobsInProgress::class,
			'languages'                    => EndpointLanguages::class,
			'assignToTranslation'          => RetranslationEndpoint::class,
			'invalidateLangMappingCache'   => InvalidateCacheEndpoint::class,
		];
	}

	private static function getUrls( $ateTab ) {
		return [
			'editor'                    => \WPML_TM_Translation_Status_Display::get_link_for_existing_job( self::JOB_ID_PLACEHOLDER ),
			'ateams'                    => $ateTab,
			'automaticSettings'         => \admin_url( UIPage::getSettings() ),
			'translateAutomaticallyDoc' => DocPage::getTranslateAutomatically(),
			'ateConsole'                => make( NoCreditPopup::class )->getUrl(),
			'translationQueue'          => \add_query_arg(
				[ 'status' => ICL_TM_NEEDS_REVIEW ],
				\admin_url( UIPage::getTranslationQueue() )
			),
			'currentUrl'                => \WPML\TM\API\Jobs::getCurrentUrl(),
			'editLanguages'             => add_query_arg( [ 'trop' => 1 ], UIPage::getLanguages() ),
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
