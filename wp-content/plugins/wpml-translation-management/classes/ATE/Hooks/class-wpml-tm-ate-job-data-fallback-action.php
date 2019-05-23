<?php

class WPML_TM_ATE_Job_Data_Fallback implements IWPML_Action {
	/** @var WPML_TM_ATE_API */
	private $ate_api;

	/**
	 * @param WPML_TM_ATE_API $ate_api
	 */
	public function __construct( WPML_TM_ATE_API $ate_api ) {
		$this->ate_api = $ate_api;
	}


	public function add_hooks() {
		add_filter( 'wpml_tm_ate_job_data_fallback', array( $this, 'get_data_from_api' ), 10, 2 );
	}

	/**
	 * @param array $data
	 * @param int   $wpml_job_id
	 *
	 * @return array
	 */
	public function get_data_from_api( array $data, $wpml_job_id ) {
		$response = $this->ate_api->get_jobs_by_wpml_ids( array( $wpml_job_id ) );
		if ( ! $response || is_wp_error( $response ) ) {
			return $data;
		}

		if ( ! isset( $response->{$wpml_job_id}->ate_job_id ) ) {
			return $data;
		}

		return array( WPML_TM_ATE_Job_Records::FIELD_ATE_JOB_ID => $response->{$wpml_job_id}->ate_job_id );
	}
}