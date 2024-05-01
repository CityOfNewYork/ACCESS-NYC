<?php

namespace WPML\TM\ATE\ClonedSites;

use WPML\FP\Fns;

class Report {

	/**
	 * @var \WPML_TM_AMS_API
	 */
	private $apiClient;

	/**
	 * @var Lock
	 */
	private $lock;

	/**
	 * @var \WPML_TM_ATE_Job_Repository
	 */
	private $ateJobsRepository;

	/**
	 * Update jobs synchronisation
	 *
	 * @var \WPML_TP_Sync_Update_Job
	 */
	private $updateJobs;

	/**
	 * @var \WPML_Translation_Job_Factory
	 */
	private $translationJobFactory;

	/**
	 * @param \WPML_TM_AMS_API $apiClient
	 * @param Lock $lock
	 * @param \WPML_TM_ATE_Job_Repository $ateJobsRepository
	 * @param \WPML_Translation_Job_Factory $translationJobFactory
	 */
	public function __construct(
		\WPML_TM_AMS_API $apiClient,
		Lock $lock,
		\WPML_TM_ATE_Job_Repository $ateJobsRepository,
		\WPML_TP_Sync_Update_Job $updateJobs,
		\WPML_Translation_Job_Factory $translationJobFactory
	) {
		$this->apiClient             = $apiClient;
		$this->lock                  = $lock;
		$this->ateJobsRepository     = $ateJobsRepository;
		$this->updateJobs            = $updateJobs;
		$this->translationJobFactory = $translationJobFactory;
	}


	/**
	 * @return true|\WP_Error
	 */
	public function move() {
		$reportResult = $this->apiClient->reportMovedSite();
		$result       = $this->apiClient->processMoveReport( $reportResult );

		if ( $result ) {
			$this->lock->unlock();
			do_action( 'wpml_tm_ate_synchronize_translators' );
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function copy() {
		return $this->copyWithStrategy( 'reportCopiedSite' );
	}

	/**
	 * @param string $migrationCode
	 *
	 * @return bool
	 */
	public function copyWithCredit( $migrationCode ) {
		return $this->copyWithStrategy( 'reportCopiedSiteWithCreditTransfer', [ $migrationCode ] );
	}

	/**
	 * @param string $copyStrategy
	 * @param mixed[] $arguments
	 *
	 * @return bool
	 */
	private function copyWithStrategy( $copyStrategy, $arguments = [] ) {
		$reportResult = call_user_func_array( [ $this->apiClient, $copyStrategy ], $arguments );
		$result       = $this->apiClient->processCopyReportConfirmation( $reportResult );

		if ( $result ) {
			$jobsInProgress = $this->ateJobsRepository->get_jobs_to_sync();
			/** @var \WPML_TM_Post_Job_Entity $jobInProgress */
			foreach ( $jobsInProgress as $jobInProgress ) {
				$jobInProgress->set_status( ICL_TM_NOT_TRANSLATED );
				$this->updateJobs->update_state( $jobInProgress );
				$this->translationJobFactory->delete_job_data( $jobInProgress->get_translate_job_id() );
			}

			$this->lock->unlock();
			do_action( 'wpml_tm_ate_synchronize_translators' );
		}

		return $result;
	}
}