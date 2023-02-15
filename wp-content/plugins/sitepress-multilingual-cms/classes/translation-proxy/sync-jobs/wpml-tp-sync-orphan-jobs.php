<?php

class WPML_TP_Sync_Orphan_Jobs {
	/** @var WPML_TM_Jobs_Repository */
	private $jobs_repository;

	/** @var WPML_TP_Sync_Update_Job */
	private $update_job;

	/**
	 * @param WPML_TM_Jobs_Repository $jobs_repository
	 * @param WPML_TP_Sync_Update_Job $update_job
	 */
	public function __construct( WPML_TM_Jobs_Repository $jobs_repository, WPML_TP_Sync_Update_Job $update_job ) {
		$this->jobs_repository = $jobs_repository;
		$this->update_job      = $update_job;
	}


	/**
	 * @return WPML_TM_Jobs_Collection
	 */
	public function cancel_orphans() {
		$params = new WPML_TM_Jobs_Search_Params(
			array(
				'scope'  => WPML_TM_Jobs_Search_Params::SCOPE_REMOTE,
				'tp_id'  => 0,
				'status' => array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ),
			)
		);

		return $this->jobs_repository->get( $params )->map( array( $this, 'cancel_job' ), true );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	public function cancel_job( WPML_TM_Job_Entity $job ) {
		$job->set_status( ICL_TM_NOT_TRANSLATED );

		return $this->update_job->update_state( $job );
	}
}
