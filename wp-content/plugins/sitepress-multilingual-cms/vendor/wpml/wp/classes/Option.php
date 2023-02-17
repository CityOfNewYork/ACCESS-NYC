<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use function WPML\FP\curryN;
use function WPML\FP\partialRight;

/**
 * @method static callable|mixed get( ...$name ) - Curried :: string → mixed
 * @method static callable|mixed getOr( ...$name, ...$default ) - Curried :: string → mixed → mixed
 * @method static callable|bool update( ...$name, ...$value ) - Curried :: string → mixed → bool
 * @method static callable|bool updateWithoutAutoLoad( ...$name, ...$value ) - Curried :: string → mixed → bool
 * @method static callable|bool delete( ...$name ) - Curried :: string → bool
 */
class Option {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'get', curryN( 1, 'get_option' ) );
		self::macro( 'getOr', curryN( 2, 'get_option' ) );

		self::macro( 'update', curryN( 2, 'update_option' ) );
		self::macro( 'updateWithoutAutoLoad', curryN( 2, partialRight( 'update_option', false ) ) );

		self::macro( 'delete', curryN( 1, 'delete_option' ) );
	}
}

Option::init();
