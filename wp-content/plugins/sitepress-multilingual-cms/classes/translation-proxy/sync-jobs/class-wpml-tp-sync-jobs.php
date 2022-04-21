<?php

class WPML_TP_Sync_Jobs {

	/** @var WPML_TM_Sync_Jobs_Status */
	private $jobs_status_sync;

	/** @var WPML_TM_Sync_Jobs_Revision */
	private $jobs_revision_sync;

	/** @var WPML_TP_Sync_Update_Job */
	private $update_job;

	/**
	 * WPML_TP_Sync_Jobs constructor.
	 *
	 * @param WPML_TM_Sync_Jobs_Status   $jobs_status_sync
	 * @param WPML_TM_Sync_Jobs_Revision $jobs_revision_sync
	 * @param WPML_TP_Sync_Update_Job    $update_job
	 */
	public function __construct(
		WPML_TM_Sync_Jobs_Status $jobs_status_sync,
		WPML_TM_Sync_Jobs_Revision $jobs_revision_sync,
		WPML_TP_Sync_Update_Job $update_job
	) {
		$this->jobs_status_sync   = $jobs_status_sync;
		$this->jobs_revision_sync = $jobs_revision_sync;
		$this->update_job         = $update_job;
	}

	/**
	 * @return WPML_TM_Jobs_Collection
	 * @throws WPML_TP_API_Exception
	 */
	public function sync() {
		return new WPML_TM_Jobs_Collection(
			$this->jobs_status_sync
			->sync()
			->append( $this->jobs_revision_sync->sync() )
			->filter_by_status( array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ), true )
			->map( array( $this->update_job, 'update_state' ) )
		);
	}
}
