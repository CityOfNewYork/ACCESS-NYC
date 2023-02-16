<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use function WPML\Container\make;

class GetATEJobsToSync implements IHandler {

	public function run( Collection $data ) {
		return Either::of(
			make( \WPML_TM_ATE_Job_Repository::class )->get_jobs_to_sync()->map_to_property( 'editor_job_id' )
		);
	}
}
