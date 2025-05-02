<?php

namespace WPML\TM\ATE\AutoTranslate\Repository;

use WPML\LIB\WP\Transient;

class CachedJobsCount implements JobsCountInterface {

	const CACHE_KEY = 'wpml-ate-jobs-count';

	/** @var JobsCountInterface $jobsCount */
	private $jobsCount;

	public function __construct( JobsCountInterface $jobsCount ) {
		$this->jobsCount = $jobsCount;
	}

	/**
	 * @return array{
	 *   allCount: int,
	 *   allAutomaticCount: int,
	 *   automaticWithoutLongstandingCount: int,
	 *   needsReviewCount: int
	 * }
	 */
	public function get( $withCache = true ): array {
		if ( $withCache ) {
			$data = Transient::get( self::CACHE_KEY );
			if ( $data && $this->validateCachedData( $data ) ) {
				return $data;
			}
		}

		$data = $this->jobsCount->get();
		Transient::set(self::CACHE_KEY, $data, 60*2 );

		return $data;
	}

	private function validateCachedData( $data ): bool {
		if ( !is_array( $data ) ) {
			return false;
		}

		$requiredKeys = [ 'allCount', 'allAutomaticCount', 'automaticWithoutLongstandingCount', 'needsReviewCount' ];

		foreach ( $requiredKeys as $key ) {
			if ( !array_key_exists( $key, $data ) || !is_int( $data[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}
}
