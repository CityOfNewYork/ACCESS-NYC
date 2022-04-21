<?php

namespace WPML\Media;

use WPML\FP\Obj;
use WPML\LIB\WP\Option as WPOption;

class Option {
	const OPTION_KEY = '_wpml_media';

	const SETUP_FINISHED = 'starting_help';

	public static function isSetupFinished() {
		return self::get( self::SETUP_FINISHED );
	}

	public static function setSetupFinished( $setupFinished = true ) {
		self::set( self::SETUP_FINISHED, $setupFinished );
	}

	private static function get( $name, $default = false ) {
		return Obj::propOr( $default, $name, WPOption::getOr( self::OPTION_KEY, [] ) );
	}

	private static function set( $name, $value ) {
		$data          = WPOption::getOr( self::OPTION_KEY, [] );
		$data[ $name ] = $value;

		WPOption::updateWithoutAutoLoad( self::OPTION_KEY, $data );
	}
}