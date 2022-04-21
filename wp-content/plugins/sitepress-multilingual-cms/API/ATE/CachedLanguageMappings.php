<?php

namespace WPML\TM\API\ATE;

use WPML\TM\ATE\API\CachedATEAPI;
use WPML\TM\ATE\API\CacheStorage\Transient;
use function WPML\Container\make;

class CachedLanguageMappings extends LanguageMappings {
	/**
	 * @return CachedATEAPI
	 */
	protected static function getATEAPI() {
		return new CachedATEAPI( make( \WPML_TM_ATE_API::class ), new Transient() );
	}
}

