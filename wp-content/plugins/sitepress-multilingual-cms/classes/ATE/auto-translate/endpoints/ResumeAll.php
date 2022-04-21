<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use function WPML\Container\make;

class ResumeAll implements IHandler {

	public function run( Collection $data ) {
		return Either::of( make( \WPML_TM_AMS_API::class )->resumeAll() );
	}
}
