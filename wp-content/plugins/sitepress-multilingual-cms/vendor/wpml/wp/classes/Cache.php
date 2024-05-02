<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Just;
use WPML\FP\Maybe;
use WPML\FP\Nothing;
use function WPML\FP\curryN;

/**
 * Class Cache
 * @package WPML\LIB\WP
 * @method static callable memorize( ...$group, ...$expire, ...$fn ) :: string → int -> callable → callable
 * @method static callable memorizeWithCheck( ...$group, ...$checkingFn, ...$expire, ...$fn ) :: string → callable → int -> callable → callable
 * @method static callable|bool set( ...$group, ...$key, ...$expire, ...$value ) :: string → string → mixed → int->bool
 * @method static callable|Just|Nothing get( ...$group, ...$key ) :: string → string → Nothing | Just( mixed )
 */
class Cache {

	const KEYS = 'WPML_WP_Cache__group_keys';

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'get', curryN( 2, [ self::class, 'getInternal' ] ) );

		self::macro( 'set', curryN( 4, function ( $group, $key, $expire, $value ) {
			$keys = self::getKeysInGroup( $group );
			if ( ! in_array( $key, $keys, true ) ) {
				$keys[] = $key;
				\wp_cache_set( $group, [ 'data' => $keys ], self::KEYS );
			}

			// Save $value in an array. We need to do this because W3TC and Redis have bug with saving null.
			return \wp_cache_set( $key, [ 'data' => $value ], $group, $expire );
		} ) );

		self::macro( 'memorizeWithCheck', curryN( 4, function ( $group, $checkingFn, $expire, $fn ) {
			return function () use ( $fn, $group, $checkingFn, $expire ) {
				$args = func_get_args();
				$key = self::_buildKeyForFunctionArguments( $args );

				$result = Cache::get( $group, $key );
				if ( Fns::isNothing( $result ) || ! $checkingFn( $result->get() ) ) {
					$result = call_user_func_array( $fn, $args );
					Cache::set( $group, $key, $expire, $result );

					return $result;
				}

				return $result->get();
			};
		} ) );

		self::macro( 'memorize', self::memorizeWithCheck( Fns::__, Fns::always( true ), Fns::__, Fns::__ ) );

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

		if ( $found && is_array( $result ) && array_key_exists( 'data', $result ) ) {
			return Maybe::just( $result['data'] );
		}

		return Maybe::nothing();
	}

	/**
	 * @param string $group
	 *
	 * @return void
	 */
	public static function flushGroup( $group ) {
		$keys = self::getKeysInGroup( $group );

		foreach ( $keys as $key ) {
			wp_cache_delete( $key, $group );
		}

		wp_cache_delete( $group, self::KEYS );
	}

	/**
	 * Clear cache for a memoized function. The function must be memoized using `memorize` or `memorizeWithCheck`.
	 * The clearMemoizedFunction must be called with the same arguments as memoized function.
	 *
	 * For example if you have a function:
	 * $fn = function( $a, $b ) { return $a + $b; };
	 * $memoizedFn = Cache::memorize( 'group', 3600, $fn );
	 * $memoizedFn( 1, 2 );
	 *
	 * Then you can clear the cache for this function by calling:
	 * Cache::clearMemoizedFunction( 'group', 1, 2 );
	 *
	 * @param string $group
	 * @param ...$functionArgs
	 *
	 * @return void
	 */
	public static function clearMemoizedFunction( $group, ...$functionArgs ) {
		self::delete( $group, self::_buildKeyForFunctionArguments( $functionArgs ) );
	}

	/**
	 * Delete a cached value using the key and group.
	 *
	 *
	 * @param string $group
	 * @param string $key
	 *
	 * @return void
	 */
	public static function delete( $group, $key ) {
		wp_cache_delete( $key, $group );

		$keys = self::getKeysInGroup( $group );
		$keys = array_values( array_diff( $keys, [ $key ] ) );
		wp_cache_set( $group, [ 'data' => $keys ], self::KEYS );
	}

	/**
	 * We store the list of keys belonging to a group in a separate key in order to be able to flush the group
	 * as many engines like Redis does not support `flush_group` function ( which was introduced in WP 6.1 ).
	 *
	 * @return array
	 */
	public static function getKeysInGroup( $group ) {
		return self::getInternal( self::KEYS, $group )->getOrElse( [] );
	}

	/**
	 * It is internal function used to build a key for a function arguments. Do not use it directly.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function _buildKeyForFunctionArguments( array $args ) {
		return serialize( $args );
	}
}

Cache::init();
