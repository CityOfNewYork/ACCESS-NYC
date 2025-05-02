<?php

namespace WPML\TM\ATE\AutoTranslate\Repository;

use WPML\TM\ATE\Jobs;

class JobsCount implements JobsCountInterface {
 	/** @var Jobs $jobs */
	private $jobs;

	public function __construct( Jobs $jobs ) {
		$this->jobs = $jobs;
	}

	/**
	 * @return array{
	 *   allCount: int,
	 *   allAutomaticCount: int,
	 *   automaticWithoutLongstandingCount: int,
	 *   needsReviewCount: int
	 * }
	 */
	public function get(): array {
		return [
			'allCount'                          => $this->jobs->getCountOfInProgress(),
			'allAutomaticCount'                 => $this->jobs->getCountOfAutomaticInProgress( true ),
			'automaticWithoutLongstandingCount' => $this->jobs->getCountOfAutomaticInProgress( false ),
			'needsReviewCount'                  => $this->jobs->getCountOfNeedsReview(),
		];
	}

}
