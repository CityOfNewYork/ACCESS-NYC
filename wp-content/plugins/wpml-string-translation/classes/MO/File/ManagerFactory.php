<?php

namespace WPML\ST\MO\File;

use function WPML\Container\make;

class ManagerFactory {

	/**
	 * @return Manager
	 * @throws \Auryn\InjectionException
	 */
	public static function create() {
		return make( Manager::class, [ ':builder' => make( Builder::class ) ] );
	}
}
