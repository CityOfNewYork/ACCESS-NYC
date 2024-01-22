<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Element\API\Languages as APILanguages;
use WPML\TM\API\ATE\CachedLanguageMappings;
use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Right;
use function WPML\FP\pipe;

class Languages implements IHandler {
	public function run( Collection $data ) {
		$isAteActive = \WPML_TM_ATE_Status::is_enabled_and_activated();

		if ( ! $isAteActive ) {
			return Right::of( [] );
		}

		$defaultLanguage = APILanguages::getDefaultCode();
		$getLanguages    = pipe(
			APILanguages::class . '::getActive',
			CachedLanguageMappings::withCanBeTranslatedAutomatically(),
			Fns::map(
				Obj::addProp(
					'is_default',
					Relation::propEq( 'code', $defaultLanguage )
				)
			),
			Obj::values()
		);

		return Right::of( $getLanguages() );
	}
}
