<?php
/**
 * @author OnTheGo Systems
 */

class WPML_TM_REST_ATE_Jobs extends WPML_TM_ATE_Required_Rest_Base {
	const CAPABILITY = 'manage_translations';

	private $ate_jobs;

	/** @var WPML_TM_ATE_Job_Repository */
	private $job_repository;

	/**
	 * WPML_TM_REST_ATE_Jobs constructor.
	 *
	 * @param WPML_TM_ATE_Jobs           $ate_jobs
	 * @param WPML_TM_ATE_Job_Repository $job_repository
	 */
	public function __construct( WPML_TM_ATE_Jobs $ate_jobs, WPML_TM_ATE_Job_Repository $job_repository ) {
		parent::__construct();
		$this->ate_jobs       = $ate_jobs;
		$this->job_repository = $job_repository;
	}

	function add_hooks() {
		$this->register_routes();
	}

	function register_routes() {
		parent::register_route(
			WPML_TM_ATE_AMS_Endpoints::STORE_JOB,
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'store_ate_job' ),
				'args'     => array(
					'wpml_job_id'  => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'integer' ),
					),
					'ate_job_data' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function store_ate_job( WP_REST_Request $request ) {
		$wpml_job_id  = $request->get_param( 'wpml_job_id' );
		$ate_job_data = $request->get_param( 'ate_job_data' );

		$this->ate_jobs->store( $wpml_job_id, $ate_job_data );

		return true;
	}

	function get_allowed_capabilities( WP_REST_Request $request ) {
		return self::CAPABILITY;
	}
}
