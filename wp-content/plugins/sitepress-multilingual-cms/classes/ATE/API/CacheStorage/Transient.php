<?php

namespace WPML\TM\ATE\API\CacheStorage;

use WPML\LIB\WP\Transient as WPTransient;

class Transient implements Storage {

	/**
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return WPTransient::getOr( $key, $default );
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function save( $key, $value ) {
		WPTransient::set( $key, $value, 3600 * 24 );
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		WPTransient::delete( $key );
	}

}