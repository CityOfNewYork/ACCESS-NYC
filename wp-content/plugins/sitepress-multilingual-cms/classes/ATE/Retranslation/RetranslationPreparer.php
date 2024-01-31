<?php

namespace WPML\TM\ATE\Retranslation;

use WPML\Collect\Support\Collection;
use WPML\FP\Obj;

class RetranslationPreparer {

	/** @var \wpdb */
	private $wpdb;

	/** @var \WPML_TM_ATE_API $ateApi */
	private $ateApi;

	/**
	 * @param \wpdb $wpdb
	 * @param \WPML_TM_ATE_API $ateApi
	 */
	public function __construct( \wpdb $wpdb, \WPML_TM_ATE_API $ateApi ) {
		$this->wpdb   = $wpdb;
		$this->ateApi = $ateApi;
	}


	/**
	 * It changes status of corresponding WPML jobs into "waiting to translator" ( means in-progress ),
	 * which will trigger the ATE Sync flow for them.
	 *
	 * @param int[] $ateJobIds
	 *
	 * @return array{int, int}
	 */
	public function delegate( array $ateJobIds ): array {
		$rowset = \wpml_collect( $this->wpdb->get_results( $this->buildSelectQuery( $ateJobIds ) ) );

		list( $jobsWhichShouldBeReFetched, $outdatedJobs ) = $rowset->partition( Obj::prop( 'is_the_most_recent_job' ) );

		if ( count( $jobsWhichShouldBeReFetched ) ) {
			$this->turnJobsIntoInProgress( $jobsWhichShouldBeReFetched );
		}

		if ( count( $outdatedJobs ) ) {
			$this->ateApi->confirm_received_job( $outdatedJobs->pluck( 'editor_job_id' )->toArray() );
		}

		return [ count( $jobsWhichShouldBeReFetched ), count( $outdatedJobs ) ];
	}

	private function buildSelectQuery( array $ateJobIds ): string {
		$isTheMostRecentJob = "
			SELECT IF ( MAX(icl_translate_job2.job_id) = icl_translate_job1.job_id , 1, 0 )
			FROM {$this->wpdb->prefix}icl_translate_job icl_translate_job2
			WHERE icl_translate_job1.rid = icl_translate_job2.rid
			
		";

		$select = "
			SELECT tranlation_status.rid, icl_translate_job1.job_id, icl_translate_job1.editor_job_id, ({$isTheMostRecentJob}) as is_the_most_recent_job
			FROM {$this->wpdb->prefix}icl_translation_status tranlation_status
			INNER JOIN {$this->wpdb->prefix}icl_translate_job icl_translate_job1 ON icl_translate_job1.rid = tranlation_status.rid
			WHERE icl_translate_job1.editor_job_id IN (" . implode( ',', $ateJobIds ) . ")
		";

		return $select;
	}

	private function turnJobsIntoInProgress( Collection $jobsWhichShouldBeReFetched ) {
		$query = "
				UPDATE {$this->wpdb->prefix}icl_translation_status
				SET `status` = %d
				WHERE rid IN (" . implode( ',', $jobsWhichShouldBeReFetched->pluck( 'rid' )->toArray() ) . ")
			";

		$query = $this->wpdb->prepare( $query, ICL_TM_WAITING_FOR_TRANSLATOR );

		$this->wpdb->query( $query );
	}
}