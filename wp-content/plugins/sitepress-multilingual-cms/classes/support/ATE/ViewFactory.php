<?php

namespace WPML\Support\ATE;

use WPML\TM\ATE\ClonedSites\SecondaryDomains;
use function WPML\Container\make;
use WPML\TM\ATE\Log\Storage;

class ViewFactory {

	public function create() {
		$logCount = make( Storage::class )->getCount();

		return new View( $logCount, make( SecondaryDomains::class ) );
	}
}
