<?php

class WPML_Translation_Jobs_Migration_Repository {

	private $jobs_repository;
	private $all_jobs = false;

	public function __construct( WPML_TM_Jobs_Repository $jobs_repository, $all_jobs = false ) {
		$this->jobs_repository = $jobs_repository;
		$this->all_jobs        = $all_jobs;
	}

	public function get() {
		return $this->jobs_repository->get( $this->get_params() )->getIterator()->getArrayCopy();
	}

	public function get_count() {
		return $this->jobs_repository->get_count( $this->get_params() );
	}

	private function get_params() {
		$params = new WPML_TM_Jobs_Search_Params();
		$params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );
		$params->set_job_types( array( WPML_TM_Job_Entity::POST_TYPE, WPML_TM_Job_Entity::PACKAGE_TYPE ) );

		if ( ! $this->all_jobs ) {
			$params->set_tp_id( null );
		}
		
		return $params;
	}
}