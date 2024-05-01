<?php

namespace WPML\TM\ATE\ClonedSites\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\LIB\WP\WordPress;
use WPML\TM\ATE\ClonedSites\Report;
use function WPML\Container\make;

class CopyWithCredits implements IHandler {

	public function run( Collection $data ) {
		$migrationCode = $data->get( 'migrationCode' );

		/** @var Report $report */
		$report = make( Report::class );

		$result = $report->copyWithCredit( $migrationCode );

		return $result ? Either::of( true ) : Either::left( 'Failed to report' );
	}

}