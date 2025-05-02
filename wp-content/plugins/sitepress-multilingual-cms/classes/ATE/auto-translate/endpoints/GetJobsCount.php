<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\TM\ATE\AutoTranslate\Repository\JobsCountInterface;
use WPML\TM\ATE\Jobs;

/**
 * The endpoint is used in the sync process to determine:
 *  - how many any ATE jobs left to sync
 *  - how many automatic ATE jobs left to sync
 *  - how many jobs needs review
 */
class GetJobsCount implements IHandler {
	/** @var JobsCountInterface $jobsCount */
	private $jobsCount;

	public function __construct( JobsCountInterface $jobsCount ) {
		$this->jobsCount = $jobsCount;
	}

	public function run( Collection $data ) {
		return Either::of( $this->jobsCount->get( $data->get( 'withCache', true ) ) );
	}
}
