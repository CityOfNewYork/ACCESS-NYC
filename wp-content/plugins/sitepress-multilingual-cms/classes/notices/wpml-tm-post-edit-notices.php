<?php

use WPML\API\Sanitize;
use WPML\TM\API\Jobs;

class WPML_TM_Post_Edit_Notices {

	const TEMPLATE_TRANSLATION_IN_PROGRESS = 'translation-in-progress.twig';
	const TEMPLATE_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS = 'edit-original-translation-in-progress.twig';
	const TEMPLATE_USE_PREFERABLY_TM_DASHBOARD = 'use-preferably-tm-dashboard.twig';
	const TEMPLATE_USE_PREFERABLY_TE = 'use-preferably-translation-editor.twig';
	const DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION = 'wpml_dismiss_post_edit_original_te_notice';
	const DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION = 'wpml_dismiss_post_edit_te_notice';
	const DISPLAY_LIMIT_TRANSLATIONS_IN_PROGRESS = 5;

	/** @var WPML_Post_Status $post_status */
	private $post_status;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var IWPML_Template_Service $template_render */
	private $template_render;

	/** @var WPML_Super_Globals_Validation $super_globals */
	private $super_globals;

	/** @var WPML_TM_Translation_Status_Display $status_display */
	private $status_display;

	/** @var WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	/** @var WPML_TM_ATE $tm_ate */
	private $tm_ate;

	/** @var WPML_TM_Rest_Job_Translator_Name $translator_name */
	private $translator_name;

	/** @var WPML_TM_Rest_Jobs_Translation_Service $translation_service */
	private $translation_service;

	/**
	 * @param WPML_Post_Status                   $post_status
	 * @param SitePress                          $sitepress
	 * @param IWPML_Template_Service             $template_render
	 * @param WPML_Super_Globals_Validation      $super_globals
	 * @param WPML_TM_Translation_Status_Display $status_display
	 * @param WPML_Translation_Element_Factory   $element_factory
	 */
	public function __construct(
		WPML_Post_Status $post_status,
		SitePress $sitepress,
		IWPML_Template_Service $template_render,
		WPML_Super_Globals_Validation $super_globals,
		WPML_TM_Translation_Status_Display $status_display,
		WPML_Translation_Element_Factory $element_factory,
		WPML_TM_ATE $tm_ate,
		WPML_TM_Rest_Job_Translator_Name $translator_name,
		WPML_TM_Rest_Jobs_Translation_Service $translation_service
	) {
		$this->post_status            = $post_status;
		$this->sitepress              = $sitepress;
		$this->template_render        = $template_render;
		$this->super_globals          = $super_globals;
		$this->status_display         = $status_display;
		$this->element_factory        = $element_factory;
		$this->tm_ate                 = $tm_ate;
		$this->translator_name		  = $translator_name;
		$this->translation_service	  = $translation_service;
	}

	public function add_hooks() {
		$request_get_trid = isset( $_GET['trid'] ) ?
			filter_var( $_GET['trid'], FILTER_SANITIZE_NUMBER_INT ) :
			'';

		$request_get_post = isset( $_GET['post'] ) ?
			filter_var( $_GET['post'], FILTER_SANITIZE_NUMBER_INT ) :
			'';

		if ( $request_get_trid || $request_get_post ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'admin_notices', array( $this, 'display_notices' ) );
		}

		add_action( 'wp_ajax_' . self::DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION, array( $this, 'do_not_display_it_again_to_user' ) );
		add_action( 'wp_ajax_' . self::DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION, array( $this, 'do_not_display_it_again' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'wpml-tm-post-edit-alert',
			WPML_TM_URL . '/res/js/post-edit-alert.js',
			array( 'jquery', 'jquery-ui-dialog' ),
			ICL_SITEPRESS_VERSION
		);
	}

	public function display_notices() {
		$trid    = $this->super_globals->get( 'trid', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
		$post_id = $this->super_globals->get( 'post', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
		$lang    = $this->super_globals->get( 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );

		if ( ! $post_id ) {
			return;
		}

		$post_element = $this->element_factory->create( $post_id, 'post' );
		$is_original  = ! $post_element->get_source_language_code();

		if ( ! $trid ) {
			$trid = $post_element->get_trid();
		}

		if ( $trid ) {
			$translations_in_progress = $is_original
				? $this->get_translations_in_progress( $post_element )
				: [];

			if (
				! empty( $translations_in_progress ) &&
				$this->should_display_it_to_user( self::DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION )
			) {
				$translations_in_progress =
					$this->prepare_translations_for_gui (
						$translations_in_progress
					);

				$msg_stale_job =
					$this->prepare_stale_jobs_for_gui(
						$translations_in_progress
					);

				$model = array(
					'warning'                  => sprintf(
						__( '%sTranslation in progress - wait before editing%s', 'wpml-translation-management' ),
						'<strong>',
						'</strong>'
					),
					'message'                  => __( 'This page that you are editing is being translated right now. If you edit now, some or all of the translation for this page may be missing. It\'s best to wait until translation completes, then edit and update the translation.', 'wpml-translation-management' ),
					'translations_in_progress' => [
						'display_limit' => self::DISPLAY_LIMIT_TRANSLATIONS_IN_PROGRESS,
						'translations'  => $translations_in_progress,
						'title'         => __( 'Waiting for translators...', 'sitepress' ),
						/* translators: %d is the number of translations. */
						'more'          => __( '...and %d more translations.', 'sitepress' ),
						'no_translator' => __( 'First available translator', 'sitepress' ),
						'msg_stale_job' => $msg_stale_job,
					],
					'go_back_button'           => __( 'Take me back', 'wpml-translation-management' ),
					'edit_anyway_button'       => __( 'I understand - continue editing', 'wpml-translation-management' ),
					'do_not_show_again'        => __( "Don't show this warning again", 'wpml-translation-management' ),
					'do_not_show_again_action' => self::DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION,
					'nonce'                    => wp_nonce_field(
						self::DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION,
						self::DO_NOT_SHOW_AGAIN_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS_ACTION,
						true,
						false
					),
				);

				echo $this->template_render->show( $model, self::TEMPLATE_EDIT_ORIGINAL_TRANSLATION_IN_PROGRESS );
			}elseif ( $this->is_waiting_for_a_translation( (int) $this->post_status->get_status( $post_id, $trid, $lang ) ) ) {
				$model = array(
					'warning' => sprintf(
						__( '%sWarning:%s You are trying to edit a translation that is currently in the process of being added using WPML.', 'wpml-translation-management' ),
						'<strong>',
						'</strong>'
					),
					'check_dashboard' => sprintf(
						__( 'Please refer to the <a href="%s">Translation Management dashboard</a> for the exact status of this translation.', 'wpml-translation-management' ),
						admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&' )
					),
				);

				echo $this->template_render->show( $model, self::TEMPLATE_TRANSLATION_IN_PROGRESS );

			} elseif (
				! $is_original &&
				WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post_id ) &&
				apply_filters( 'wpml_tm_show_page_builders_translation_editor_warning', true, $post_id ) &&
			    $this->should_display_it( self::DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION )
			) {

				$model = array(
					'warning' => sprintf(
						__( '%sWarning:%s You are trying to edit a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.', 'wpml-translation-management' ),
						'<strong>',
						'</strong>'
					),
				    'go_back_button'         => __( 'Go back', 'wpml-translation-management' ),
				    'edit_anyway_button'     => __( 'Edit anyway', 'wpml-translation-management' ),
				    'open_in_te_button'      => __( 'Open in Translation Editor', 'wpml-translation-management' ),
				    'translation_editor_url' => $this->get_translation_editor_link( $post_element ),
					'do_not_show_again'      => __( "Don't show this warning again", 'wpml-translation-management' ),
					'do_not_show_again_action' => self::DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION,
					'nonce'                  => wp_nonce_field(
						self::DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION,
						self::DO_NOT_SHOW_AGAIN_USE_PREFERABLY_TE_ACTION,
						true,
						false
					),
				);

				echo $this->template_render->show( $model, self::TEMPLATE_USE_PREFERABLY_TE );
			}

		} elseif ( $post_element->is_translatable()
		           && WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post_id )
		){
			$model = array(
				'warning' => sprintf(
					__('%sWarning:%s You are trying to add a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.' , 'wpml-translation-management'),
					'<strong>',
					'</strong>'
				),
				'use_tm_dashboard' => sprintf(
					__( 'You should use <a href="%s">Translation management dashboard</a> to send the original document to translation.' , 'wpml-translation-management' ),
					admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' )
				),
			);

			echo $this->template_render->show( $model, self::TEMPLATE_USE_PREFERABLY_TM_DASHBOARD );
		}
	}

	public function do_not_display_it_again_to_user() {
		$action = Sanitize::stringProp( 'action', $_POST );
		if( is_string( $action ) && $this->is_valid_request( $action ) ){
			update_user_option( get_current_user_id(), $action, 1 );
		}
	}

	public function do_not_display_it_again() {
		$action = Sanitize::stringProp( 'action', $_POST );
		if( is_string( $action ) && $this->is_valid_request( $action ) ){
			update_option( $action, 1, false );
		}
	}

	/**
	 * @return bool
	 */
	private function is_valid_request( $action ) {
		return isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], $action );
	}

	/**
	 * @return bool
	 */
	private function should_display_it_to_user( $action ) {
		return false === get_user_option( $action );
	}

	/**
	 * @return bool
	 */
	private function should_display_it( $action ) {
		return false === get_option( $action );
	}

	/**
	 * @param WPML_Translation_Element $post_element
	 *
	 * @return bool
	 */
	private function get_translations_in_progress( $post_element ) {
		$translations = $this->sitepress->get_element_translations(
			$post_element->get_trid(),
			$post_element->get_wpml_element_type()
		);

		if ( ! is_array( $translations ) || empty( $translations ) ) {
			return [];
		}

		$wpml_element_translations = wpml_tm_load_element_translations();
		$translations_in_progress  = [];

		foreach ( $translations as $translation ) {
			if ( $translation->original ) {
				continue;
			}

			$job = Jobs::getTridJob(
				$post_element->get_trid(),
				$translation->language_code
			);

			if (
				$job
				&& ! $this->is_waiting_for_a_translation( $job->status )
			) {
				// Translation is completed - no need for further checks.
				continue;
			}

			// For the case that the user opened ATE from post edit screen
			// and comes back to the post edit screen, WPML needs to fetch
			// the ATE status directly from ATE API as it's not in the DB yet.
			//
			// The check for HTTP_REFERER is quite fragile (can be manipulated)
			// but totally fine for this case as it's only about showing
			// a warning or not. In addition the ATE UI "Complete" and "Back"
			// links are baked in JS so it's not possible to open them in a
			// new tab/window by usual browser controls.
			if (
				$job
				&& 'ate' === $job->editor
				&& array_key_exists( 'referer', $_GET )
				&& 'ate' === $_GET['referer']
				&& $this->tm_ate->is_translation_method_ate_enabled()
				&& $this->tm_ate->is_translation_ready_for_post(
					$post_element->get_trid(),
					$translation->language_code
				)
			) {
				continue;
			}

			if (
				$this->is_waiting_for_a_translation(
					$wpml_element_translations->get_translation_status(
						$post_element->get_trid(),
						$translation->language_code
					)
				)
			) {
				$translations_in_progress[] = $job;
			}
		}

		return $translations_in_progress;
	}

	private function prepare_translations_for_gui( $translations ) {
		// Prepare data for GUI.
		$translations = array_map(
			[ $this, 'prepare_translation_for_gui' ],
			$translations
		);

		// Sort languages by language name (as usual).
		usort(
			$translations,
			function ( $a, $b ) {
				if ( $a['to_language'] == $b['to_language'] ) {
					return 0;
				}

				return $a['to_language'] > $b['to_language'] ? 1 : - 1;
			}
		);

		return $translations;
	}

	private function prepare_translation_for_gui( $job ) {
		if (
			! is_object( $job )
			|| ! property_exists( $job, 'language_code' )
			|| ! property_exists( $job, 'to_language' )
		) {
			return;
		}

		$since = null;
		if ( property_exists( $job, 'elements' ) && is_array( $job->elements ) ) {
			foreach ( $job->elements as $element ) {
				if (
					! property_exists( $element, 'timestamp' )
					|| ! property_exists( $element, 'field_finished' )
					|| 0 !== (int) $element->field_finished
				) {
					// No valid element or already finished.
					continue;
				}

				$element_since = strtotime( $element->timestamp );

				$since = null === $since || $element_since < $since
					? $element_since
					: $since;
			}
		}

		$is_automatic = property_exists( $job, 'automatic' )
			? (bool) $job->automatic
			: false;

		$editor_job_id = property_exists( $job, 'editor_job_id' )
			? (int) $job->editor_job_id
			: null;

		return [
			'to_language'   => $job->to_language,
			'is_automatic'  => $is_automatic,
			'flag'          => $this->sitepress->get_flag_image( $job->language_code ),
			'translator'    => $this->translator_name_by_job( $job ),
			'since'         => $since,
			'waiting_for'   => $this->waiting_for_x_time( $since ),
			'editor_job_id' => $editor_job_id,
		];
	}

	private function translator_name_by_job( $job ) {
		if (
			property_exists( $job, 'automatic' )
			&& 1 === (int) $job->automatic
		) {
			// Automatic Translation.
			return __( 'Automatic translation', 'sitepress' );
		}

		if (
			property_exists( $job, 'translation_service' )
			&& is_numeric( $job->translation_service )
		) {
			// Translation Service.
			return $this->translation_service
				->get_name( $job->translation_service );
		}

		// Translator.
		return property_exists( $job, 'translator_id' )
			? $this->translator_name->get( $job->translator_id )
			: null;
	}

	private function waiting_for_x_time( $since_timestamp ) {
		if ( empty( $since_timestamp ) ) {
			return '';
		}

		$since = new \DateTime();
		$since->setTimestamp( $since_timestamp );

		$interval = $since->diff( new \DateTime() );

		// Use: x day(s) if translation is longer than 24 hours in progress.
		if ( $interval->days > 0 ) {
			return sprintf(
				/* translators: %d is for the number of day(s). */
				_n( '%d day', '%d days', $interval->days, 'sitepress' ),
				$interval->days
			);
		}

		// Use: x hour(s) if translation is longer than 1 hour in progress.
		if ( $interval->h > 0 ) {
			return sprintf(
				/* translators: %d is for the number of hour(s). */
				_n( '%d hour', '%d hours', $interval->h, 'sitepress' ),
				$interval->h
			);
		}

		// Use: x minute(s) if translation is less than a hour in progress.
		return sprintf(
			/* translators: %d is for the number of minute(s). */
			_n( '%d minute', '%d minutes', $interval->i, 'sitepress' ),
			$interval->i
		);
	}

	/**
	 * Stale jobs are automatic translated jobs, which are in progress for
	 * 1 or more days. As there is a limit of jobs being shown, this method
	 * makes sure to move the stale job to the top of the list and returns
	 * an error message, asking the user to contact support with all
	 * stale job ate ids.
	 *
	 * @param array $translations
	 * @return string
	 */
	private function prepare_stale_jobs_for_gui( &$translations ) {
		$stale_ids = [];
		foreach ( $translations as $k => $translation ) {
			if ( $translation === null || ! $translation['is_automatic'] ) {
				continue;
			}

			$since = new \DateTime();
			$since->setTimestamp( $translation['since'] );
			$interval = $since->diff( new \DateTime() );

			if ( 0 === $interval->days ) {
				// All good with this translation.
				continue;
			}

			$stale_ids[] = $translation['editor_job_id'];

			if (
				count( $translations ) >
					self::DISPLAY_LIMIT_TRANSLATIONS_IN_PROGRESS
			) {
				// More translations in progress as the dialog shows.
				// Move this stale automatic translation to top.
				unset( $translations[ $k ] );
				array_unshift( $translations, $translation );
			}
		}

		if ( empty( $stale_ids ) ) {
			return '';
		}

		return sprintf(
			/* translators: %1$1s and %2$2s is used for adding html tags to make WPML support a link. %3$s is a list of numeric ids. */
			_n(
				'Something went wrong with automatic translation. Please contact %1$1sWPML support%2$2s and report that the following automatic translation is stuck: %3$3s',
				'Something went wrong with automatic translation. Please contact %1$1sWPML support%2$2s and report that the following automatic translations are stuck: %3$3s',
				count( $stale_ids ),
				'sitepress'
			),
			'<a href="https://wpml.org/forums/forum/english-support/?utm_source=wpmlplugin&utm_campaign=content-editing&utm_medium=post-editor&utm_term=translation-in-progress/" target="_blank">',
			'</a>',
			implode( ', ', $stale_ids )
		);
	}


	/**
	 * @param int|null $translation_status
	 *
	 * @return bool
	 */
	private function is_waiting_for_a_translation( $translation_status ) {
		return ! is_null( $translation_status )
		       && $translation_status > 0
		       && $translation_status != ICL_TM_DUPLICATE
		       && $translation_status < ICL_TM_COMPLETE;
	}

	/**
	 * @param WPML_Translation_Element $post_element
	 *
	 * @return string
	 */
	private function get_translation_editor_link( $post_element ) {
		$post_id             = $post_element->get_id();
		$source_post_element = $post_element->get_source_element();

		if ( $source_post_element ) {
			$post_id = $source_post_element->get_id();
		}

		$url = $this->status_display->filter_status_link(
			'#', $post_id, $post_element->get_language_code(), $post_element->get_trid()
		);

		return remove_query_arg( 'return_url', $url );
	}
}
