<?php

namespace WPML\TM\ATE\AutoTranslate\Repository;

interface JobsCountInterface {
	/**
	 * @return array{
	 *   allCount: int,
	 *   allAutomaticCount: int,
	 *   automaticWithoutLongstandingCount: int,
	 *   needsReviewCount: int
	 * }
	 */
	public function get(): array;

}
