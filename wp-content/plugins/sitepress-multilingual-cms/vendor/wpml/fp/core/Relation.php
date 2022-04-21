<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|bool equals( ...$a, ...$b ) - Curried :: a->b->bool
 * @method static callable|bool lt( ...$a, ...$b ) - Curried :: a->b->bool
 * @method static callable|bool lte( ...$a, ...$b ) - Curried :: a->b->bool
 * @method static callable|bool gt( ...$a, ...$b ) - Curried :: a->b->bool
 * @method static callable|bool gte( ...$a, ...$b ) - Curried :: a->b->bool
 * @method static callable|bool propEq( ...$prop, ...$value, ...$obj ) - Curried :: String → a → array → bool
 * @method static callable|array sortWith( ...$comparators, ...$array ) - Curried :: [(a, a) → int] → [a] → [a]
 */
class Relation {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'equals', curryN( 2, function ( $a, $b ) {
			return $a === $b;
		} ) );

		self::macro( 'lt', curryN( 2, function ( $a, $b ) {
			if ( is_string( $a ) && is_string( $b ) ) {
				return strcmp( $a, $b ) < 0;
			}

			return $a < $b;
		} ) );

		self::macro( 'gt', curryN( 2, function ( $a, $b ) {
			return self::lt( $b, $a );
		} ) );

		self::macro( 'lte', curryN( 2, function ( $a, $b ) {
			return ! self::gt( $a, $b );
		} ) );

		self::macro( 'gte', curryN( 2, function ( $a, $b ) {
			return ! self::lt( $a, $b );
		} ) );

		self::macro( 'propEq', curryN( 3, function ( $prop, $value, $obj ) {
			return Obj::prop( $prop, $obj ) === $value;
		} ) );

		self::macro( 'sortWith', curryN( 2, function ( $comparators, $array ) {
			$compareFn = function( $a, $b ) use ( $comparators ) {
				foreach ( $comparators as $comparator ) {
					$v = $comparator( $a, $b );
					if ( $v !== 0 ) {
						return $v;
					}
				}
				return 0;
			};
			return Lst::sort( $compareFn, $array );
		}));

	}
}

Relation::init();
