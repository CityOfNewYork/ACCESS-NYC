<?php

class WPML_TM_ATE_Job_Repository {
	/** @var WPML_TM_Jobs_Repository */
	private $job_repository;

	/** @var WPML_TM_ATE_Job_Records */
	private $ate_job_records;

	/**
	 * @param WPML_TM_Jobs_Repository $job_repository
	 */
	public function __construct( WPML_TM_Jobs_Repository $job_repository, WPML_TM_ATE_Job_Records $ate_job_records ) {
		$this->job_repository  = $job_repository;
		$this->ate_job_records = $ate_job_records;
	}

	/**
	 * @return WPML_TM_Jobs_Collection
	 */
	public function get_jobs_to_sync() {
		$search_params = new WPML_TM_Jobs_Search_Params();
		$search_params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_LOCAL );
		$search_params->set_status( self::get_statuses_to_sync() );
		$search_params->set_job_types( array( WPML_TM_Job_Entity::POST_TYPE, WPML_TM_Job_Entity::PACKAGE_TYPE ) );

		return $this->job_repository->get( $search_params )->filter( array( $this, 'should_ate_job_be_synced' ) );
	}

	/**
	 * We want to synchronize an ATE job if it has an "in progress" status
	 * or if the `is_editing` flag is set to `true`.
	 *
	 * @param WPML_TM_Post_Job_Entity $job
	 *
	 * @return bool
	 */
	public function should_ate_job_be_synced( WPML_TM_Post_Job_Entity $job ) {
		return $job->is_ate_job()
			&& (
					in_array( $job->get_status(), self::get_in_progress_statuses() )
					|| $this->ate_job_records->is_editing_job( $job->get_translate_job_id() )
		       );
	}

	/** @return array */
	public static function get_in_progress_statuses() {
		return array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS );
	}

	/** @return array */
	public static function get_statuses_to_sync() {
		return array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS, ICL_TM_COMPLETE );
	}
}