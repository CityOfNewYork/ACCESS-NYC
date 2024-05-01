<?php

namespace WPML\TM\ATE\ClonedSites\Endpoints;

use WPML\FP\Either;
use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\LIB\WP\WordPress;
use WPML\TM\ATE\ClonedSites\Report;
use function WPML\Container\make;

class Move implements IHandler {

	public function run( Collection $data ) {
		/** @var Report $report */
		$report = make( Report::class );

		$result = $report->move();

		return is_wp_error( $result ) ? Either::left( 'Failed to report' ) : Either::of( true );
	}
}