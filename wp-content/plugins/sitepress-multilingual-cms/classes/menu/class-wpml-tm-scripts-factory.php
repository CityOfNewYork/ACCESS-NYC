<?php

use WPML\Element\API\Languages;
use WPML\FP\Obj;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\TM\API\Basket;
use WPML\TM\TranslationDashboard\FiltersStorage;
use WPML\TM\TranslationDashboard\SentContentMessages;
use WPML\Core\WP\App\Resources;
use WPML\UIPage;
use function WPML\Container\make;

/**
 * @author OnTheGo Systems
 */
class WPML_TM_Scripts_Factory {
	private $ate;
	private $auth;
	private $endpoints;
	private $strings;

	public function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'wpml_tm_translators_view_strings', array( $this, 'filter_translators_view_strings' ), 10, 2 );
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function admin_enqueue_scripts() {
		$this->register_otgs_notices();

		wp_register_script(
			'ate-translation-editor-classic',
			WPML_TM_URL . '/dist/js/ate-translation-editor-classic/app.js',
			array(),
			false,
			true
		);

		if ( WPML_TM_Page::is_tm_dashboard() ) {
			$this->localize_script( 'wpml-tm-dashboard' );
			wp_enqueue_script( 'wpml-tm-dashboard' );
		}
		if (
			WPML_TM_Page::is_tm_translators()
			|| UIPage::isTroubleshooting( $_GET )
		) {
			wp_enqueue_style( 'otgs-notices' );
			$this->localize_script( 'wpml-tm-settings' );
			wp_enqueue_script( 'wpml-tm-settings' );

			$this->create_ate()->init_hooks();
		}
		if ( WPML_TM_Page::is_settings() ) {
			wp_enqueue_style( 'otgs-notices' );
			$this->localize_script( 'wpml-settings-ui' );
			$this->create_ate()->init_hooks();
		}

		if ( WPML_TM_Page::is_translation_queue() && WPML_TM_ATE_Status::is_enabled() ) {
			$this->localize_script( 'ate-translation-queue' );
			wp_enqueue_script( 'ate-translation-queue' );
			wp_enqueue_script( 'ate-translation-editor-classic' );
			wp_enqueue_style( 'otgs-notices' );
		}

		if ( WPML_TM_Page::is_dashboard() ) {
			$this->load_pick_up_box_scripts();
		}

		if ( WPML_TM_Page::is_settings() ) {
			wp_enqueue_style(
				'wpml-tm-multilingual-content-setup',
				WPML_TM_URL . '/res/css/multilingual-content-setup.css',
				array(),
				ICL_SITEPRESS_VERSION
			);
		}

		if ( WPML_TM_Page::is_notifications_page() ) {
			wp_enqueue_style(
				'wpml-tm-translation-notifications',
				WPML_TM_URL . '/res/css/translation-notifications.css',
				array(),
				ICL_SITEPRESS_VERSION
			);
		}
	}

	private function load_pick_up_box_scripts() {
		wp_enqueue_style( 'otgs-notices' );

		global $iclTranslationManagement;

		$currentLanguageCode = FiltersStorage::getFromLanguage();

		/** @var \WPML_Translation_Management $tmManager */
		$tmManager = wpml_translation_management();

		$currentTranslationService     = TranslationProxy::get_current_service();
		$isCurrentServiceAuthenticated = TranslationProxy_Service::is_authenticated( $currentTranslationService );

		$getTargetLanguages = \WPML\FP\pipe(
			Languages::class . '::getActive',
			Obj::removeProp( $currentLanguageCode ),
			Languages::withFlags(),
			CachedLanguageMappings::withCanBeTranslatedAutomatically(),
			Obj::values()
		);

		$data = [
			'name' => 'WPML_TM_DASHBOARD',
			'data' => [
				'endpoints'            => [
					'duplicate'              => \WPML\TM\TranslationDashboard\Endpoints\Duplicate::class,
					'displayNewMessage'      => \WPML\TM\TranslationDashboard\Endpoints\DisplayNeedSyncMessage::class,
					'setTranslateEverything' => \WPML\TranslationMode\Endpoint\SetTranslateEverything::class,
					'getCredits'             => \WPML\TM\ATE\AutoTranslate\Endpoint\GetCredits::class,
				],
				'strings'              => [
					'numberOfTranslationStringsSingle' => __( '%d translation job', 'wpml-translation-management' ),
					'numberOfTranslationStringsMulti'  => __( '%d translation jobs', 'wpml-translation-management' ),
					'stringsSentToTranslationSingle'   => __(
						'%s has been sent to remote translators',
						'wpml-translation-management'
					),
					'stringsSentToTranslationMulti'    => __(
						'%s have been sent to remote translators',
						'wpml-translation-management'
					),

					'buttonText'        => __( 'Check status and get translations', 'wpml-translation-management' ),
					'progressText'      => __(
						"Checking translation jobs status. Please don't close this page!",
						'wpml-translation-management'
					),
					'progressJobsCount' => __( 'You are downloading %d jobs', 'wpml-translation-management' ),

					'statusChecked'            => __( 'Status checked:', 'wpml-translation-management' ),
					'dismissNotice'            => __( 'Dismiss this notice.', 'wpml-translation-management' ),
					'noTranslationsDownloaded' => __(
						'none of your translation jobs have been completed',
						'wpml-translation-management'
					),
					'translationsDownloaded'   => __(
						'%d translation jobs have been finished and applied.',
						'wpml-translation-management'
					),

					'errorMessage' => __(
						'A communication error has appeared. Please wait a few minutes and try again.',
						'wpml-translation-management'
					),

					'lastCheck' => __( 'Last check: %s', 'wpml-translation-management' ),
					'never'     => __( 'never', 'wpml-translation-management' ),
				],
				'debug'                => defined( 'WPML_POLLING_BOX_DEBUG_MODE' ) && WPML_POLLING_BOX_DEBUG_MODE,
				'statusIcons'          => [
					'completed'   => $iclTranslationManagement->status2icon_class( ICL_TM_COMPLETE, false ),
					'canceled'    => $iclTranslationManagement->status2icon_class( ICL_TM_NOT_TRANSLATED, false ),
					'progress'    => $iclTranslationManagement->status2icon_class( ICL_TM_IN_PROGRESS, false ),
					'needsUpdate' => $iclTranslationManagement->status2icon_class( ICL_TM_NEEDS_UPDATE, false ),
				],
				'sendingToTranslation' => [
					'targetLanguages'       => $getTargetLanguages(),
					'iclnonce'              => wp_create_nonce( 'pro-translation-icl' ),
					'translationReviewMode' => \WPML\Setup\Option::getReviewMode( null ),
					'settings' => [
						'defaultLanguageDisplayName'                  => Languages::getDefault()['display_name'],
						'isInDefaultLanguage'                         => Languages::getDefaultCode() === $currentLanguageCode,
						'shouldUseBasket'                             => Basket::shouldUse( $currentLanguageCode ),
						'isATEActive'                                 => WPML_TM_ATE_Status::is_enabled_and_activated(),
						'hasAnyLocalTranslators'                      => wpml_tm_load_blog_translators()->has_translators(),
						'hasAnyTranslationServices'                   => $currentTranslationService && $isCurrentServiceAuthenticated,
						'doesTranslationServiceRequireAuthentication' => $currentTranslationService && ! $isCurrentServiceAuthenticated,
						'doesServiceRequireTranslators'               => $currentTranslationService && $tmManager->service_requires_translators(),
						'currentTranslationServiceName'               => $currentTranslationService ? Obj::prop( 'name', $currentTranslationService ) : null,
					],
					'urls' => [
						'translatorsTab' => UIPage::getTMTranslators() . '#js-wpml-active-service-wrapper',
					],
				],
				'sentContentMessages'  => make( SentContentMessages::class )->get(),
			],
		];

		$enqueueApp = Resources::enqueueApp( 'translationDashboard' );
		$enqueueApp( $data );
	}

	public function register_otgs_notices() {
		if ( ! wp_style_is( 'otgs-notices', 'registered' ) ) {
			wp_register_style(
				'otgs-notices',
				ICL_PLUGIN_URL . '/res/css/otgs-notices.css',
				array( 'sitepress-style' )
			);
		}
	}

	/**
	 * @param $handle
	 *
	 * @throws \InvalidArgumentException
	 */
	public function localize_script( $handle, $additional_data = array() ) {
		wp_localize_script( $handle, 'WPML_TM_SETTINGS', $this->build_localize_script_data( $additional_data ) );
	}

	public function build_localize_script_data($additional_data = array()  ) {
		$data = array(
			'hasATEEnabled'      => WPML_TM_ATE_Status::is_enabled(),
			'restUrl'            => untrailingslashit( rest_url() ),
			'restNonce'          => wp_create_nonce( 'wp_rest' ),
			'syncJobStatesNonce' => wp_create_nonce( 'sync-job-states' ),
			'ate'                => $this->create_ate()
			                        ->get_script_data(),
			'currentUser'   => null,
		);

		$data = array_merge( $data, $additional_data );

		$current_user = wp_get_current_user();
		if ( $current_user && $current_user->ID > 0 ) {
			$filtered_current_user      = clone $current_user;
			$filtered_current_user_data = new \stdClass();
			$blacklistedProps           = [ 'user_pass' ];

			foreach ( $current_user->data as $prop => $value ) {
				if ( in_array( $prop, $blacklistedProps ) ) {
					continue;
				}

				$filtered_current_user_data->$prop = $value;
			}
			$filtered_current_user->data = $filtered_current_user_data;

			$data['currentUser'] = $filtered_current_user;
		}

		return $data;
	}

	/**
	 * @return WPML_TM_MCS_ATE
	 * @throws \InvalidArgumentException
	 */
	public function create_ate() {
		if ( ! $this->ate ) {
			$this->ate = new WPML_TM_MCS_ATE(
				$this->get_authentication(),
				$this->get_endpoints(),
				$this->create_ate_strings()
			);
		}

		return $this->ate;
	}

	private function get_authentication() {
		if ( ! $this->auth ) {
			$this->auth = new WPML_TM_ATE_Authentication();
		}

		return $this->auth;
	}

	private function get_endpoints() {
		if ( ! $this->endpoints ) {
			$this->endpoints = WPML\Container\make( 'WPML_TM_ATE_AMS_Endpoints' );
		}

		return $this->endpoints;
	}

	private function create_ate_strings() {
		if ( ! $this->strings ) {
			$this->strings = new WPML_TM_MCS_ATE_Strings( $this->get_authentication(), $this->get_endpoints() );
		}

		return $this->strings;
	}

	/**
	 * @param array $strings
	 * @param bool  $all_users_have_subscription
	 *
	 * @return array
	 */
	public function filter_translators_view_strings( array $strings, $all_users_have_subscription ) {
		if ( WPML_TM_ATE_Status::is_enabled() ) {
			$strings['ate'] = $this->create_ate_strings()
								->get_status_HTML(
									$this->get_ate_activation_status(),
									$all_users_have_subscription
								);
		}

		return $strings;
	}

	/**
	 * @return string
	 */
	private function get_ate_activation_status() {
		$status = $this->create_ate_strings()
					   ->get_status();
		if ( $status !== WPML_TM_ATE_Authentication::AMS_STATUS_ACTIVE ) {
			$status = $this->fetch_and_update_ate_activation_status();
		}

		return $status;
	}

	/**
	 * @return string
	 */
	private function fetch_and_update_ate_activation_status() {
		$ams_api = WPML\Container\make( WPML_TM_AMS_API::class );
		$ams_api->get_status();

		return $this->create_ate_strings()
					->get_status();
	}
}
