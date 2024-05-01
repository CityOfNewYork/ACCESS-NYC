<?php

namespace ACFML;

use WPML\WP\OptionManager;

class Options {

	const GROUP = 'acfml';

	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		return ( new OptionManager() )->get( self::GROUP, $key, $default );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public static function set( $key, $value ) {
		( new OptionManager() )->set( self::GROUP, $key, $value );
	}
}
