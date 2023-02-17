<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\TM\API\ATE\LanguageMappings;

class CheckLanguageSupport {

	public function run( Collection $data ) {
		return Either::of( $data->get( 'languages', [] ) )
		             ->map( Fns::map( Obj::objOf( 'code' ) ) )
		             ->map( LanguageMappings::withCanBeTranslatedAutomatically() );
	}

}