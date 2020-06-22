<?php

/**
 * Class WPML_Non_Persistent_Cache
 *
 * Implements non-persistent cache based on an array. Suitable to cache objects during single page load.
 */
class WPML_Non_Persistent_Cache {

	/**
	 * @var array Cached objects.
	 */
	private static $cache = array();

	/**
	 * Retrieves the data contents from the cache, if it exists.
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @param bool   $found Whether the key was found in the cache (passed by reference).
	 *                      Disambiguates a return of false, a storable value.
	 *
	 * @return mixed|bool
	 */
	public static function get( $key, $group = 'default', &$found = null ) {
		if (
			isset( self::$cache[ $group ] ) &&
			( isset( self::$cache[ $group ][ $key ] ) || array_key_exists( $key, self::$cache[ $group ] ) )
		) {
			$found = true;

			return self::$cache[ $group ][ $key ];
		}
		$found = false;

		return false;
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $data  Data to store in cache.
	 * @param string $group Cache group.
	 *
	 * @return bool
	 */
	public static function set( $key, $data, $group = 'default' ) {
		if ( is_object( $data ) ) {
			$data = clone $data;
		}
		self::$cache[ $group ][ $key ] = $data;

		return true;
	}

	/**
	 * Executes callback function and caches its result.
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Callback function.
	 * @param string   $group    Cache group.
	 *
	 * @return bool
	 */
	public static function execute_and_cache( $key, $callback, $group = 'default' ) {
		$data = self::get( $key, $group, $found );
		if ( ! $found ) {
			$data = $callback();
			self::set( $key, $data, $group );
		}

		return $data;
	}

	/**
	 * Flush cache.
	 *
	 * @return bool
	 */
	public static function flush() {
		self::$cache = array();

		return true;
	}

	/**
	 * Flush cache group.
	 *
	 * @param array|string $groups Cache group name.
	 *
	 * @return bool
	 */
	public static function flush_group( $groups = 'default' ) {
		$groups = (array) $groups;
		foreach ( $groups as $group ) {
			unset( self::$cache[ $group ] );
		}

		return true;
	}
}
