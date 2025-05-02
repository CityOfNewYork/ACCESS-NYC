<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use function WPML\Container\make;

class GetATEJobsToSync implements IHandler {

	public function run( Collection $data ) {
		/** @var \WPML_TM_ATE_Job_Repository $jobsRepo */
		$jobsRepo = make( \WPML_TM_ATE_Job_Repository::class );

		return Either::of( $jobsRepo->get_jobs_to_sync( true, true ) );
	}
}
