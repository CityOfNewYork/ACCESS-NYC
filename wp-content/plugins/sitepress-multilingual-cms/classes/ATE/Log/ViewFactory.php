<?php

namespace WPML\TM\ATE\Log;

use function WPML\Container\make;

class ViewFactory {

	public function create() {
		$logs = make( Storage::class )->getAll();

		return new View( $logs );
	}
}
