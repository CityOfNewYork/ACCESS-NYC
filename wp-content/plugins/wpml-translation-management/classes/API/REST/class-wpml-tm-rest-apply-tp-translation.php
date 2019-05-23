<?php

class WPML_TM_REST_Apply_TP_Translation extends WPML_REST_Base {
	/** @var WPML_TP_Apply_Translations */
	private $apply_translations;

	public function __construct( WPML_TP_Apply_Translations $apply_translations ) {
		parent::__construct( 'wpml/tm/v1' );

		$this->apply_translations     = $apply_translations;
	}

	public function add_hooks() {
		$this->register_routes();
	}

	public function register_routes() {
		parent::register_route( '/tp/apply-translations',
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'apply_translations' ),
			)
		);
	}

	/**
	 * @return WP_Error|int|array
	 */
	public function apply_translations( WP_REST_Request $request ) {
		try {
			$params = $request->get_json_params();

			if ( $params ) {
				if ( ! isset( $params['original_element_id'] ) ) {
					$params = array_filter( $params, array( $this, 'validate_job' ) );
					if ( ! $params ) {
						return array();
					}
				}
			}

			return $this->apply_translations->apply( $params )->map( array( $this, 'map_jobs_to_array' ) );
		} catch ( Exception $e ) {
			return new WP_Error( 400, $e->getMessage() );
		}
	}

	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return array( WPML_Manage_Translations_Role::CAPABILITY, WPML_Translator_Role::CAPABILITY );
	}

	public function map_jobs_to_array( WPML_TM_Job_Entity $job ) {
		return array(
			'id'     => $job->get_id(),
			'type'   => $job->get_type(),
			'status' => $job->get_status(),
		);
	}

	/**
	 * @param array $job
	 *
	 * @return bool
	 */
	private function validate_job( array $job ) {
		return isset( $job['id'] ) && isset( $job['type'] ) && $this->validate_job_type( $job['type'] );
	}

	private function validate_job_type( $value ) {
		return in_array( $value, array(
			WPML_TM_Job_Entity::POST_TYPE,
			WPML_TM_Job_Entity::STRING_TYPE,
			WPML_TM_Job_Entity::PACKAGE_TYPE
		) );
	}
}