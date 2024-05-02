<?php

namespace WPML\PB\BeaverBuilder\BeaverThemer;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	public function create() {

		if ( self::isActive() ) {
			return [
				new LocationHooks(),
			];
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public static function isActive() {
		return defined( 'FL_THEME_BUILDER_VERSION' );
	}
}
