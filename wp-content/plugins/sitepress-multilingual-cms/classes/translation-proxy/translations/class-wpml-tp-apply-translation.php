<?php

class WPML_TP_Apply_Translations {
	/** @var WPML_TM_Jobs_Repository */
	private $jobs_repository;

	/** @var WPML_TP_Apply_Single_Job */
	private $apply_single_job;

	/** @var WPML_TP_Sync_Jobs */
	private $tp_sync;

	/**
	 * @param WPML_TM_Jobs_Repository  $jobs_repository
	 * @param WPML_TP_Apply_Single_Job $apply_single_job
	 * @param WPML_TP_Sync_Jobs        $tp_sync
	 */
	public function __construct(
		WPML_TM_Jobs_Repository $jobs_repository,
		WPML_TP_Apply_Single_Job $apply_single_job,
		WPML_TP_Sync_Jobs $tp_sync
	) {
		$this->jobs_repository  = $jobs_repository;
		$this->apply_single_job = $apply_single_job;
		$this->tp_sync          = $tp_sync;
	}

	/**
	 * @param array $params
	 *
	 * @return WPML_TM_Jobs_Collection
	 * @throws WPML_TP_API_Exception
	 */
	public function apply( array $params ) {
		$jobs = $this->get_jobs( $params );

		if ( $this->has_in_progress_jobs( $jobs ) ) {
			$jobs = $this->sync_jobs( $jobs );
		}
		$cancelled_jobs = $jobs->filter_by_status( ICL_TM_NOT_TRANSLATED );

		$downloaded_jobs = new WPML_TM_Jobs_Collection(
			$jobs->filter_by_status( [ ICL_TM_TRANSLATION_READY_TO_DOWNLOAD, ICL_TM_COMPLETE ] )
				 ->map( array( $this->apply_single_job, 'apply' ) )
		);

		return $downloaded_jobs->append( $cancelled_jobs );
	}

	/**
	 * @param WPML_TM_Jobs_Collection $jobs
	 *
	 * @return bool
	 */
	private function has_in_progress_jobs( WPML_TM_Jobs_Collection $jobs ) {
		return count( $jobs->filter_by_status( ICL_TM_IN_PROGRESS ) ) > 0;
	}

	/**
	 * @param array $params
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	private function get_jobs( array $params ) {
		if ( $params ) {
			if ( isset( $params['original_element_id'], $params['element_type'] ) ) {
				$jobs = $this->get_jobs_by_original_element( $params['original_element_id'], $params['element_type'] );
			} else {
				$jobs = $this->get_jobs_by_ids( $params );
			}
		} else {
			$jobs = $this->get_all_ready_jobs();
		}

		return $jobs;
	}

	/**
	 * @param int    $original_element_id
	 * @param string $element_type
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	private function get_jobs_by_original_element( $original_element_id, $element_type ) {
		$params = new WPML_TM_Jobs_Search_Params();
		$params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );
		$params->set_original_element_id( $original_element_id );
		$params->set_job_types( $element_type );

		return $this->jobs_repository->get_collection( $params );
	}

	/**
	 * @param array $params
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	private function get_jobs_by_ids( array $params ) {
		$jobs = array();
		foreach ( $params as $param ) {
			$jobs[] = $this->jobs_repository->get_job( $param['id'], $param['type'] );
		}

		return new WPML_TM_Jobs_Collection( $jobs );
	}

	/**
	 * @return WPML_TM_Jobs_Collection
	 */
	private function get_all_ready_jobs() {
		return $this->jobs_repository->get_collection(
			new WPML_TM_Jobs_Search_Params(
				array(
					'status' => array( ICL_TM_TRANSLATION_READY_TO_DOWNLOAD ),
					'scope'  => WPML_TM_Jobs_Search_Params::SCOPE_REMOTE,
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
	private function sync_jobs( WPML_TM_Jobs_Collection $jobs ) {
		$synced_jobs = $this->tp_sync->sync();
		/** @var WPML_TM_Job_Entity $job */
		foreach ( $jobs as $job ) {
			foreach ( $synced_jobs as $synced_job ) {
				if ( $job->is_equal( $synced_job ) ) {
					$job->set_status( $synced_job->get_status() );
					break;
				}
			}
		}

		return $jobs;
	}
}
