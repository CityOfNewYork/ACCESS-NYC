<?php
namespace WPML\CLI\Core\Commands;

use function WPML\Container\make;

class ClearCacheFactory implements IWPML_Core {

	/**
	 * @return ClearCache
	 * @throws \WPML\Auryn\InjectionException If it's not possible to create the instance (see \WPML\Auryn\Injector::make).
	 */
	public function create() {
		return make( ClearCache::class );
	}
}
