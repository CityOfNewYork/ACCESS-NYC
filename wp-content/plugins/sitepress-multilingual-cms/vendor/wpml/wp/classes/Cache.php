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

		self::macro( 'memorize', self::memorizeWithCheck( Fns::__, Fns::T(), Fns::__ ) );

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

		return $found ? Maybe::just( $result ) : Maybe::nothing();
	}

	/**
	 * @param string $group
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|true
	 */
	public static function setInternal( $group, $key, $value ) {
		return wp_cache_set( $key, $value, $group );
	}

}

Cache::init();
