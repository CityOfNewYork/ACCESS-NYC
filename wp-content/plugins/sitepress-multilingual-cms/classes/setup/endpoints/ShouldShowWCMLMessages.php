<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class ShouldShowWCMLMessages implements IHandler {

	public function run( Collection $data ){
		return Either::of( self::getOption() );
	}

	/**
	 * @return bool
	 */
	public static function getOption() {
		return (bool) apply_filters( 'wpml_wizard_display_wcml_messages', false );
	}

}
