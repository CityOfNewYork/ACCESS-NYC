<?php

class WPML_TM_TS_Instructions_Hooks implements IWPML_Action {
	/** @var WPML_TM_TS_Instructions_Notice */
	private $notice;

	/**
	 * WPML_TM_TS_Instructions_Hooks constructor.
	 *
	 * @param WPML_TM_TS_Instructions_Notice $notice
	 */
	public function __construct( WPML_TM_TS_Instructions_Notice $notice ) {
		$this->notice = $notice;
	}


	public function add_hooks() {
		add_action( 'wpml_tp_project_created', array( $this, 'display_message' ), 10, 3 );
		add_action( 'init', array( $this, 'add_hooks_on_init' ), 10, 0 );

		add_action( 'wpml_tp_service_de_authorized', array( $this, 'dismiss' ), 10, 0 );
		add_action( 'wpml_tp_service_dectivated', array( $this, 'dismiss' ), 10, 0 );
	}

	/**
	 * @param stdClass $service
	 * @param stdClass $project
	 * @param array    $icl_translation_projects
	 */
	public function display_message( $service, $project, array $icl_translation_projects ) {
		$is_first_project_ever = empty( $icl_translation_projects );

		if ( $is_first_project_ever || ! $this->has_completed_remote_jobs() ) {
			$this->notice->add_notice( $service );
		}
	}

	public function add_hooks_on_init() {
		if ( $this->notice->exists() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );
			add_action( 'wp_ajax_translation_service_instruction_dismiss', array( $this, 'dismiss' ), 10, 0 );
		}
	}

	public function enqueue_scripts() {
		$handle = 'wpml-tm-translation-service-instruction';

		wp_register_script(
			$handle,
			WPML_TM_URL . '/dist/js/translationServiceInstruction/app.js',
			array(),
			WPML_TM_VERSION
		);

		$data = array(
			'restUrl'     => untrailingslashit( rest_url() ),
			'restNonce'   => wp_create_nonce( 'wp_rest' ),
			'ate'         => null,
			'currentUser' => wp_get_current_user(),
		);

		wp_localize_script( $handle, 'WPML_TM_SETTINGS', $data );

		wp_enqueue_script( $handle );
	}

	public function dismiss() {
		$this->notice->remove_notice();
	}

	/**
	 * @return bool
	 */
	private function has_completed_remote_jobs() {
		$search_params = new WPML_TM_Jobs_Search_Params();
		$search_params->set_status( array( ICL_TM_COMPLETE ) );
		$search_params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );

		return wpml_tm_get_jobs_repository()->get_count( $search_params ) > 0;
	}
}
