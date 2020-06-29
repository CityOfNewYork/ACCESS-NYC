<?php

namespace WPML\Media\Duplication;


class HooksFactory extends AbstractFactory {
	public function create() {
		if ( self::shouldActivateHooks() ) {
			return [ Hooks::class, 'add' ];
		}

		return null;
	}
}