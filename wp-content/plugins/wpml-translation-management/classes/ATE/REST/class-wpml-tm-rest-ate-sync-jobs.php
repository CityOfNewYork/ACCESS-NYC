<?php

class WPML_TM_REST_ATE_Sync_Jobs extends WPML_TM_ATE_Required_Rest_Base {
	/** @var array  */
	private $capabilities = array( 'manage_translations', 'translate' );

	/** @var TranslationManagement */
	private $tm_core;

	/** @var WPML_TM_ATE_Jobs_Actions */
	private $jobs_action;

	/**
	 * @param TranslationManagement    $tm_core
	 * @param WPML_TM_ATE_Jobs_Actions $jobs_action
	 */
	public function __construct( TranslationManagement $tm_core, WPML_TM_ATE_Jobs_Actions $jobs_action ) {
		parent::__construct();

		$this->tm_core     = $tm_core;
		$this->jobs_action = $jobs_action;
	}


	function add_hooks() {
		$this->register_routes();
	}

	function register_routes() {
		parent::register_route( WPML_TM_ATE_AMS_Endpoints::SYNC_JOBS,
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'sync' ),
				'args'     => array(
					'jobIds' => array(
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'is_array' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'array_of_integers' ),
					),
				)
			) );
	}

	public function sync( WP_REST_Request $request ) {
		try {
			$ate_jobs = array_map( array( $this->tm_core, 'get_translation_job' ), $request->get_param( 'jobIds' ) );

			$updated_jobs = array();
			if ( $ate_jobs ) {
				$updated_jobs = $this->jobs_action->update_jobs( null, $ate_jobs, true );
			}

			return $updated_jobs;
		} catch ( Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}
	}

	function get_allowed_capabilities( WP_REST_Request $request ) {
		return $this->capabilities;
	}

	public function validate_permission( WP_REST_Request $request ) {
		if ( current_user_can( 'administrator' ) ) {
			return true;
		}

		return parent::validate_permission( $request );
	}
}
