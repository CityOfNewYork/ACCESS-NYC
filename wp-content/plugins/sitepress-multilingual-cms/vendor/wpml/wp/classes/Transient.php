<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use function WPML\FP\curryN;

/**
 * @method static callable|mixed get( ...$name ) - Curried :: string → mixed
 * @method static callable|mixed getOr( ...$name, ...$default ) - Curried :: string → mixed → mixed
 * @method static callable|mixed set( ...$name, ...$value, ...$expiration ) - Curried :: string → mixed → int -> mixed
 * @method static callable|mixed delete( ...$name ) - Curried :: string → mxied
 */
class Transient {
	use Macroable;

	public static function init() {
		self::macro( 'get', curryN( 1, 'get_transient' ) );
		self::macro( 'getOr', curryN( 2, function ( $key, $default ) {
			return self::get( $key ) ?: $default;
		} ) );

		self::macro( 'set', curryN( 3, 'set_transient' ) );
		self::macro( 'delete', curryN( 1, 'delete_transient' ) );
	}

}

Transient::init();