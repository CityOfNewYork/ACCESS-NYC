<?php

namespace WPML\API;

use WPML\FP\Lst;
use WPML\FP\Obj;

class Settings {

	const WPML_DOWNLOADED_LOCALES_KEY = 'wpml_downloaded_locales';

	/**
	 * @param string $key
	 * @param bool|mixed $default
	 *
	 * @return bool|mixed
	 */
	public static function get( $key, $default = false ) {
		return self::getOr( $default, $key );
	}

	/**
	 * @param mixed $default
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public static function getOr( $default, $key ) {
		global $sitepress;
		return $sitepress->get_setting( $key, $default );
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function set( $key, $value ) {
		global $sitepress;
		return $sitepress->set_setting( $key, $value, false );
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function setAndSave( $key, $value ) {
		global $sitepress;
		return $sitepress->set_setting( $key, $value, true );
	}

	/**
	 * Updates the setting using the sub key and value.
	 * Assumes that the setting found by the main key is an array or object
	 *
	 * @param string $key
	 * @param string $subKey
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function assoc( $key, $subKey, $value ) {
		return self::setAndSave( $key, Obj::assoc( $subKey, $value, self::getOr([], $key ) ) );
	}

	public static function pathOr( $default, $path ) {
		$key = Lst::nth( 0, $path );

		return Obj::pathOr( $default, Lst::drop( 1, $path ), self::getOr( [], $key ) );
	}
}
