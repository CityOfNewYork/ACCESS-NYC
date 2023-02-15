<?php

namespace WPML\TM\ATE\Retry;

use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Relation;
use WPML\TM\API\Jobs;
use WPML_TM_ATE_Job_Repository;
use function WPML\FP\pipe;

class Process {

	const JOBS_PROCESSED_PER_REQUEST = 10;

	/** @var WPML_TM_ATE_Job_Repository $ateRepository */
	private $ateRepository;

	/** @var Trigger $trigger */
	private $trigger;

	public function __construct(
		WPML_TM_ATE_Job_Repository $ateRepository,
		Trigger $trigger
	) {
		$this->ateRepository = $ateRepository;
		$this->trigger       = $trigger;
	}

	/**
	 * @param array $jobsToProcess
	 *
	 * @return Result
	 */
	public function run( $jobsToProcess ) {
		$result = new Result();

		if ( $jobsToProcess ) {
			$result = $this->retry( $result, wpml_collect( $jobsToProcess ) );
		} else {
			$result = $this->runRetryInit( $result );
		}

		if ( $result->jobsToProcess->isEmpty() && $this->trigger->isRetryRequired() ) {
			$this->trigger->setLastRetry( time() );
		}

		return $result;
	}

	/**
	 * @param Result $result
	 *
	 * @return Result
	 */
	private function runRetryInit( Result $result ) {
		$wpmlJobIds = $this->getWpmlJobIdsToRetry();

		if ( $this->trigger->isRetryRequired() && ! $wpmlJobIds->isEmpty() ) {
			$result = $this->retry( $result, $wpmlJobIds );
		}

		return $result;
	}

	/**
	 * @param Result $result
	 * @param Collection $jobs
	 *
	 * @return Result
	 */
	private function retry( Result $result, Collection $jobs ) {
		$jobsChunks            = $jobs->chunk( self::JOBS_PROCESSED_PER_REQUEST );
		$result->processed     = $this->handleJobs( $jobsChunks->shift() );
		$result->jobsToProcess = $jobsChunks->flatten( 1 );

		return $result;
	}

	/**
	 * @return Collection
	 */
	private function getWpmlJobIdsToRetry() {
		return wpml_collect( $this->ateRepository->get_jobs_to_retry()->map_to_property( 'translate_job_id' ) );
	}

	/**
	 * @param Collection $items
	 *
	 * @return array $items [[wpmlJobId, status, ateJobId], ...]
	 */
	private function handleJobs( Collection $items ) {
		do_action( 'wpml_added_translation_jobs', [ 'local' => $items->toArray() ], Jobs::SENT_RETRY );

		return $items->toArray();
	}
}
