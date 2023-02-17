<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use function WPML\FP\curryN;

/**
 * Class Cache
 * @package WPML\LIB\WP
 * @method static callable|mixed memorize( ...$group, ...$fn ) :: string → callable → mixed
 * @method static callable|mixed memorizeWithCheck( ...$group, ...$checkingFn, ...$fn ) :: string → callable → callable → mixed
 * @method static callable|bool set( ...$group, ...$key, ...$value ) :: string → string → mixed → bool
 * @method static callable|Maybe get( ...$group, ...$key ) :: string → string → Nothing | Just( mixed )
 */
class Cache {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'get', curryN( 2, [ self::class, 'getInternal' ] ) );
		self::macro( 'set', curryN( 3, [ self::class, 'setInternal' ] ) );

		self::macro( 'memorizeWithCheck', curryN( 3, function ( $group, $checkingFn, $fn ) {
			return function () use ( $fn, $group, $checkingFn ) {
				$args = func_get_args();
				$key  = serialize( $args );

				$result = Cache::get( $group, $key );
				if ( Fns::isNothing( $result ) || ! $checkingFn( $result->get() ) ) {
					$result = call_user_func_array( $fn, $args );
					Cache::set( $group, $key, $result );

					return $result;
				}
				return $result->get();
			};
		} ) );

		self::macro( 'memorize', self::memorizeWithCheck( Fns::__, Fns::always( true ), Fns::__ ) );

	}

	/**
	 * @param string $group
	 * @param string $key
	 *
	 * @return \WPML\FP\Just|\WPML\FP\Nothing
	 */
	public static function getInternal( $group, $key ) {
		$found  = false;
		$result = wp_cache_get( $key, $group, false, $found );

		if( $found && is_array( $result ) && array_key_exists( 'data', $result ) ) {
			return  Maybe::just( $result['data'] );
		}

		return Maybe::nothing();
	}

	/**
	 * @param string $group
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|true
	 */
	public static function setInternal( $group, $key, $value ) {
		// Save $value in an array. We need to do this because W3TC and Redis have bug with saving null.
		return wp_cache_set( $key, [ 'data' => $value ], $group );
	}

}

Cache::init();
