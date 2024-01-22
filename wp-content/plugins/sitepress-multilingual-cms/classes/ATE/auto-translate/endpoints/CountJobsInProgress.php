<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\TM\ATE\Jobs as ATEJobs;

/**
 * It returns number of automatic jobs in progress.
 * It is used as an additional safety mechanism in Translate Everything process.
 * As ATE delivers translations also in the background via the public endpoint,
 * we may end up in the situation that some jobs on JS layer are still marked as in progress
 * and the sync endpoint returns an empty collection. Due to that, we display the counter greater than 0
 * while everything is already completed.
 */
class CountJobsInProgress implements IHandler {
	public function run( Collection $data ) {
		return Either::of( ATEJobs::getCountOfAutomaticInProgress() );
	}
}