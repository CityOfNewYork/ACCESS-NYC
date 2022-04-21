<?php

namespace WPML\TM\ATE\ClonedSites;

use WPML\FP\Fns;

class Report {
	const REPORT_TYPE_COPY = 'copy';
	const REPORT_TYPE_MOVE = 'move';

	/**
	 * @var \WPML_TM_AMS_API
	 */
	private $apiClient;

	/**
	 * @var ApiCommunication
	 */
	private $apiCommunicationHandler;

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
	 * @param ApiCommunication $apiCommunicationHandler
	 * @param \WPML_TM_ATE_Job_Repository $ateJobsRepository
	 * @param \WPML_Translation_Job_Factory $translationJobFactory
	 */
	public function __construct(
		\WPML_TM_AMS_API $apiClient,
		ApiCommunication $apiCommunicationHandler,
		\WPML_TM_ATE_Job_Repository $ateJobsRepository,
		\WPML_TP_Sync_Update_Job $updateJobs,
		\WPML_Translation_Job_Factory $translationJobFactory
	) {
		$this->apiClient               = $apiClient;
		$this->apiCommunicationHandler = $apiCommunicationHandler;
		$this->ateJobsRepository       = $ateJobsRepository;
		$this->updateJobs              = $updateJobs;
		$this->translationJobFactory   = $translationJobFactory;
	}

	/**
	 * @param string $reportType
	 *
	 * @return bool
	 */
	public function report( $reportType ) {
		$reportCallback = \wpml_collect( [
			self::REPORT_TYPE_COPY => $this->reportCopiedSite(),
			self::REPORT_TYPE_MOVE => $this->reportMovedSite(),
		] )->get( $reportType, Fns::always( Fns::always( false ) ) );

		$reportResult = $reportCallback();

		if ($reportResult) {
			do_action( 'wpml_tm_ate_synchronize_translators' );
		}

		return $reportResult;
	}

	private function reportCopiedSite() {
		return function () {
			$reportResult = $this->apiClient->reportCopiedSite();
			$isConfirmed  = $this->apiClient->processCopyReportConfirmation( $reportResult );

			if ( $isConfirmed ) {
				$jobsInProgress = $this->ateJobsRepository->get_jobs_to_sync();
				/** @var \WPML_TM_Post_Job_Entity $jobInProgress */
				foreach ( $jobsInProgress as $jobInProgress ) {
					$jobInProgress->set_status( ICL_TM_NOT_TRANSLATED );
					$this->updateJobs->update_state( $jobInProgress );
					$this->translationJobFactory->delete_job_data( $jobInProgress->get_translate_job_id() );
				}
				$this->apiCommunicationHandler->unlockClonedSite();
			}

			return $isConfirmed;
		};
	}

	private function reportMovedSite() {
		return function () {
			$reportResult      = $this->apiClient->reportMovedSite();
			$movedSuccessfully = $this->apiClient->processMoveReport( $reportResult );

			if ( $movedSuccessfully ) {
				$this->apiCommunicationHandler->unlockClonedSite();
			}

			return $movedSuccessfully;
		};
	}
}