<?php

namespace WPML\ST\TranslationFile;

use WPML\ST\MO\File\ManagerFactory;
use function WPML\Container\make;

class UpdateHooksFactory {
	/** @return UpdateHooks */
	public static function create() {
		static $instance;

		if ( ! $instance ) {
			$instance = make( UpdateHooks::class, [ ':file_manager' => ManagerFactory::create() ] );
		}

		return $instance;
	}
}