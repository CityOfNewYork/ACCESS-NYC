<?php

namespace WPML\TM\ATE\Retry;

use WPML\Collect\Support\Collection;

class Result {
	/** @var Collection */
	public $jobsToProcess;

	/** @var array[wpmlJobId] */
	public $processed = [];

	public function __construct() {
		$this->jobsToProcess = wpml_collect();
	}
}
