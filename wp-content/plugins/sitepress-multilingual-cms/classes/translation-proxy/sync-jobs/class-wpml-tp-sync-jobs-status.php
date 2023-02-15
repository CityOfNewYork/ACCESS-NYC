<?php

class WPML_TM_Sync_Jobs_Status {

	/** @var WPML_TM_Jobs_Repository */
	private $jobs_repository;

	/** @var WPML_TP_Jobs_API */
	private $tp_api;

	/**
	 * WPML_TM_Sync_Jobs_Status constructor.
	 *
	 * @param WPML_TM_Jobs_Repository $jobs_repository
	 * @param WPML_TP_Jobs_API        $tp_api
	 */
	public function __construct( WPML_TM_Jobs_Repository $jobs_repository, WPML_TP_Jobs_API $tp_api ) {
		$this->jobs_repository = $jobs_repository;
		$this->tp_api          = $tp_api;
	}

	/**
	 * @return WPML_TM_Jobs_Collection
	 * @throws WPML_TP_API_Exception
	 */
	public function sync() {
		return $this->update_tp_state_of_jobs(
			$this->jobs_repository->get(
				new WPML_TM_Jobs_Search_Params(
					array(
						'status' => array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ),
						'scope'  => WPML_TM_Jobs_Search_Params::SCOPE_REMOTE,
					)
				)
			)
		);
	}

	/**
	 * @param WPML_TM_Jobs_Collection $jobs
	 *
	 * @return WPML_TM_Jobs_Collection
	 * @throws WPML_TP_API_Exception
	 */
	private function update_tp_state_of_jobs( WPML_TM_Jobs_Collection $jobs ) {
		$tp_ids = $this->extract_tp_id_from_jobs( $jobs );

		if ( $tp_ids ) {
			$tp_statuses = $this->tp_api->get_jobs_statuses( $tp_ids );
			foreach ( $tp_statuses as $job_status ) {
				$job = $jobs->get_by_tp_id( $job_status->get_tp_id() );
				if ( $job ) {
					$job->set_status( WPML_TP_Job_States::map_tp_state_to_local( $job_status->get_status() ) );
					$job->set_ts_status( $job_status->get_ts_status() );
					if ( WPML_TP_Job_States::CANCELLED === $job_status->get_status() ) {
						do_action( 'wpml_tm_canceled_job_notification', $job );
					}
				}
			}
		}

		return $jobs;
	}

	/**
	 * @param WPML_TM_Jobs_Collection $jobs
	 *
	 * @return array
	 */
	private function extract_tp_id_from_jobs( WPML_TM_Jobs_Collection $jobs ) {
		$tp_ids = array();
		foreach ( $jobs as $job ) {
			$tp_ids[] = $job->get_tp_id();
		}

		return $tp_ids;
	}

}
