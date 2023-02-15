<?php

namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\Setup\Option;

class SetSecondaryLanguages implements IHandler {
	public function run( Collection $data ) {
		return Either::of( $data )
		             ->map( Obj::prop( 'languages' ) )
		             ->map( Fns::tap( [ Option::class, 'setTranslationLangs' ] ) )
		             ->map( Lst::join( ', ' ) )
		             ->map( Str::concat('success: ') );
	}
}
