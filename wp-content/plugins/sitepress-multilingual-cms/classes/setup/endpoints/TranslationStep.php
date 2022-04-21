<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Right;
use WPML\Setup\Option;

class TranslationStep implements IHandler {

	public function run( Collection $data ) {
		Option::setTranslationMode( $data->get('whoMode') );
		return Right::of( true );
	}

}
