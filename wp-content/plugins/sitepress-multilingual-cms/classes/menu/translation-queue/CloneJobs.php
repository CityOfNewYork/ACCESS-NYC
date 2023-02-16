<?php

namespace WPML\TM\Menu\TranslationQueue;

use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\API\Job\Map;
use WPML_Element_Translation_Job;
use WPML_TM_Editors;
use WPML_TM_ATE_Jobs;
use WPML\TM\ATE\JobRecords;
use WPML_TM_ATE_API;

class CloneJobs {
	/**
	 * @var WPML_TM_ATE_Jobs
	 */
	private $ateJobs;

	/**
	 * @var WPML_TM_ATE_API
	 */
	private $apiClient;

	/**
	 * @param WPML_TM_ATE_Jobs $ateJobs
	 * @param WPML_TM_ATE_API  $apiClient
	 */
	public function __construct( WPML_TM_ATE_Jobs $ateJobs, WPML_TM_ATE_API $apiClient ) {
		$this->ateJobs   = $ateJobs;
		$this->apiClient = $apiClient;
	}

	/**
	 * @param WPML_Element_Translation_Job $jobObject
	 * @param int|null $sentFrom
	 */
	public function cloneCompletedATEJob( WPML_Element_Translation_Job $jobObject, $sentFrom = null ) {
		if ( (int) $jobObject->get_status_value() === ICL_TM_COMPLETE ) {
			$ateJobId = $this->ateJobs->get_ate_job_id( $jobObject->get_id() );
			$result   = $this->apiClient->clone_job( $ateJobId, $jobObject, $sentFrom );
			if ( $result ) {
				$this->ateJobs->store( $jobObject->get_id(), [ JobRecords::FIELD_ATE_JOB_ID => $result['id'] ] );
				$this->ateJobs->set_wpml_status_from_ate( $jobObject->get_id(), $result['ate_status'] );
			}
		}
	}

	/**
	 * It creates a corresponding ATE job for WPML Job if such ATE job does not exist yet
	 *
	 * @param int $wpmlJobId
	 * @return bool
	 */
	public function maybeCloneWPMLJob( $wpmlJobId ) {
		if ( ! $this->ateJobs->get_ate_job_id( $wpmlJobId ) ) {
			$params = json_decode( wp_json_encode( [
				'jobs' => [ wpml_tm_create_ATE_job_creation_model( $wpmlJobId ) ]
			] ), true );

			$response = $this->apiClient->create_jobs( $params );

			if ( ! is_wp_error( $response ) && Obj::prop( 'jobs', $response ) ) {
				$this->ateJobs->store( $wpmlJobId, [ JobRecords::FIELD_ATE_JOB_ID => Obj::path( [ 'jobs', Map::fromJobId( $wpmlJobId ) ], $response ) ] );
				wpml_tm_load_old_jobs_editor()->set( $wpmlJobId, WPML_TM_Editors::ATE );
				$this->ateJobs->warm_cache( [ $wpmlJobId ] );

				return true;
			}
		}

		return false;
	}
}
