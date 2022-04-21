<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\Plugins;
use WPML\Setup\Option;

class CheckTMAllowed implements IHandler {

	public function run( Collection $data ) {
		$isTMAllowed = Option::isTMAllowed();

		if ( $isTMAllowed === null ) {
			$isTMAllowed = Plugins::isTMAllowed();
			Option::setTMAllowed( $isTMAllowed );
		}

		return Either::right( $isTMAllowed );
	}
}
