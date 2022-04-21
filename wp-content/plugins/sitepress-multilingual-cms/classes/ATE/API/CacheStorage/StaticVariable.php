<?php

namespace WPML\TM\ATE\API\CacheStorage;

use WPML\FP\Obj;

class StaticVariable implements Storage {
	/** @var array */
	private static $cache = [];

	/** @var self */
	private static $instance;

	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return Obj::propOr( $default, $key, self::$cache );
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function save( $key, $value ) {
		self::$cache[ $key ] = $value;
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		self::$cache = [];
	}
}