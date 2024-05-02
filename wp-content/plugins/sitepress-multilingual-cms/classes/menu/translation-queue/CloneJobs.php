<?php

namespace WPML\TM\Menu\TranslationQueue;

use WPML\FP\Either;
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
	 * Number of microseconds to wait until an API call is repeated again in the case of failure.
	 *
	 * @var int
	 */
	private $repeatInterval;

	/**
	 * @param WPML_TM_ATE_Jobs $ateJobs
	 * @param WPML_TM_ATE_API  $apiClient
	 * @param int              $repeatInterval
	 */
	public function __construct( WPML_TM_ATE_Jobs $ateJobs, WPML_TM_ATE_API $apiClient, $repeatInterval = 5000000 ) {
		$this->ateJobs        = $ateJobs;
		$this->apiClient      = $apiClient;
		$this->repeatInterval = $repeatInterval;
	}

	/**
	 * @param WPML_Element_Translation_Job $jobObject
	 * @param int|null                     $sentFrom
	 * @param bool                         $hasBeenAlreadyRepeated
	 *
	 * @return Either<WPML_Element_Translation_Job>
	 */
	public function cloneCompletedATEJob( WPML_Element_Translation_Job $jobObject, $sentFrom = null, $hasBeenAlreadyRepeated = false ) {
		$ateJobId = (int) $jobObject->get_basic_data_property('editor_job_id');
		$result   = $this->apiClient->clone_job( $ateJobId, $jobObject, $sentFrom );
		if ( $result ) {
			$this->ateJobs->store( $jobObject->get_id(), [ JobRecords::FIELD_ATE_JOB_ID => $result['id'] ] );

			return Either::of( $jobObject );
		} elseif ( ! $hasBeenAlreadyRepeated ) {
			usleep( $this->repeatInterval );

			return $this->cloneCompletedATEJob( $jobObject, $sentFrom, true );
		} else {
			return Either::left( $jobObject );
		}
	}

	/**
	 * It creates a corresponding ATE job for WPML Job if such ATE job does not exist yet
	 *
	 * @param int $wpmlJobId
	 * @return bool
	 */
	public function cloneWPMLJob( $wpmlJobId ) {
		$params = json_decode( (string) wp_json_encode( [
			'jobs' => [ wpml_tm_create_ATE_job_creation_model( $wpmlJobId ) ]
		] ), true );

		$response = $this->apiClient->create_jobs( $params );

		if ( ! is_wp_error( $response ) && Obj::prop( 'jobs', $response ) ) {
			$this->ateJobs->store( $wpmlJobId, [ JobRecords::FIELD_ATE_JOB_ID => Obj::path( [ 'jobs', Map::fromJobId( $wpmlJobId ) ], $response ) ] );
			wpml_tm_load_old_jobs_editor()->set( $wpmlJobId, WPML_TM_Editors::ATE );
			$this->ateJobs->warm_cache( [ $wpmlJobId ] );

			return true;
		}

		return false;
	}
}
