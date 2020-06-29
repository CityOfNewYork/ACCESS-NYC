<?php

class WPML_WP_Cache {

	/** @var string Key name under which array of all group keys is stored */
	const KEYS = 'WPML_WP_Cache__group_keys';

	/** @var string Group name */
	private $group;

	/**
	 * WPML_WP_Cache constructor.
	 *
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 */
	public function __construct( $group = '' ) {
		$this->group = $group;
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @param int|string $key    The key under which the cache contents are stored.
	 * @param bool       $found  Optional. Whether the key was found in the cache (passed by reference).
	 *                           Disambiguates a return of false, a storable value. Default null.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache
	 *                    contents on success
	 */
	public function get( $key, &$found = null ) {
		$value = wp_cache_get( $key, $this->group, false, $found );
		if ( is_array( $value ) && array_key_exists( 'data', $value ) ) {
			// We know that we have set something in the cache.
			$found = true;

			return $value['data'];
		} else {
			$found = false;

			return $value;
		}
	}

	/**
	 * Saves the data to the cache.
	 *
	 * @param int|string $key    The cache key to use for retrieval later.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 *
	 * @return bool False on failure, true on success
	 */
	public function set( $key, $data, $expire = 0 ) {
		$keys = $this->get_keys();
		if ( ! in_array( $key, $keys, true ) ) {
			$keys[] = $key;
			wp_cache_set( self::KEYS, $keys, $this->group );
		}

		// Save $value in an array. We need to do this because W3TC and Redis have bug with saving null.
		return wp_cache_set( $key, [ 'data' => $data ], $this->group, $expire );
	}

	/**
	 * Removes the cache contents matching key and group.
	 */
	public function flush_group_cache() {
		$keys = $this->get_keys();

		foreach ( $keys as $key ) {
			wp_cache_delete( $key, $this->group );
		}

		wp_cache_delete( self::KEYS, $this->group );
	}

	public function execute_and_cache( $key, $callback ) {
		list( $result, $found ) = $this->get_with_found( $key );
		if ( ! $found ) {
			$result = $callback();
			$this->set( $key, $result );
		}

		return $result;
	}

	/**
	 * @param string $key
	 *
	 * @return array {
	 *    @type mixed   $result @see Return value of \wp_cache_get.
	 *    @type bool    $found @see `$found` argument of \wp_cache_get.
	 * }
	 */
	public function get_with_found( $key ) {
		$found  = false;
		$result = $this->get( $key, $found );

		return [ $result, $found ];
	}

	/**
	 * Get stored group keys.
	 *
	 * @return array
	 */
	private function get_keys() {
		$found = false;
		$keys  = wp_cache_get( self::KEYS, $this->group, false, $found );
		if ( $found && is_array( $keys ) ) {
			return $keys;
		}

		return [];
	}
}
