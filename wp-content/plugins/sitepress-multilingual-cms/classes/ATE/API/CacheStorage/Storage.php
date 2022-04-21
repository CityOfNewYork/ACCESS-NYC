<?php

namespace WPML\TM\ATE\API\CacheStorage;

interface Storage {
	/**
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null );

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function save( $key, $value );

	/**
	 * @param string $key
	 */
	public function delete( $key );
}