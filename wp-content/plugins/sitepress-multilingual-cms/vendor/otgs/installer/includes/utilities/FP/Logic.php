<?php

namespace OTGS\Installer\FP;

use OTGS\Installer\Collect\Support\Macroable;

/**
 * @method static callable|bool not( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isNotNull( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|mixed ifElse( ...$predicate, ...$first, ...$second, ...$data ) - Curried :: ( a->bool )->callable->callable->callable
 * @method static callable when( ...$predicate, ...$fn ) - Curried :: ( a->bool )->callable->callable
 * @method static callable unless( ...$predicate, ...$fn ) - Curried :: ( a->bool )->callable->callable
 * @method static callable cond( ...$conditions, ...$fn ) - Curried :: [( a->bool ), callable]->callable
 * @method static callable both( ...$a, ...$b, ...$data ) - Curried :: ( a → bool ) → ( a → bool ) → a → bool
 * @method static callable|bool allPass( ...$predicates, ...$data ) - Curried :: [( *… → bool )] → ( *… → bool )
 * @method static callable|bool anyPass( ...$predicates, ...$data ) - Curried :: [( *… → bool )] → ( *… → bool )
 * @method static callable complement( ...$fn ) - Curried :: ( *… → * ) → ( *… → bool )
 * @method static callable|mixed defaultTo( ...$a, ...$b ) - Curried :: a → b → a | b
 * @method static callable|bool either( ...$a, ...$b ) - Curried :: ( *… → bool ) → ( *… → bool ) → ( *… → bool )
 * @method static callable|mixed until ( ...$predicate, ...$transform, ...$data ) - Curried :: ( a → bool ) → ( a → a ) → a → a
 * @method static callable|bool propSatisfies( ...$predicate, ...$prop, ...$data ) - Curried :: ( a → bool ) → String → [String => a] → bool
 * @method static callable|bool isArray ( ...$a ) - Curried :: a → bool
 * @method static callable|bool isMappable ( ...$a ) - Curried :: a → bool
 * @method static callable|bool isEmpty( ...$a ) - Curried:: a → bool
 * @method static callable|mixed firstSatisfying( ...$predicate, ...$functions, ...$data ) - Curried:: callable->callable[]->mixed->mixed
 * @method static callable|bool isTruthy( ...$data ) - Curried:: mixed->bool
 */
class Logic {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'not', curryN( 1, function ( $v ) { return ! Fns::value( $v ); } ) );
		self::macro( 'isNotNull', curryN( 1, pipe( 'is_null', self::not() ) ) );
		self::macro( 'ifElse', curryN( 4, function ( callable $predicate, callable $first, callable $second, $data ) {
			return $predicate( $data ) ? $first( $data ) : $second( $data );
		} ) );
		self::macro( 'when', curryN( 3, function ( callable $predicate, callable $fn, $data ) {
			return $predicate( $data ) ? $fn( $data ) : $data;
		} ) );
		self::macro( 'unless', curryN( 3, function ( callable $predicate, callable $fn, $data ) {
			return $predicate( $data ) ? $data : $fn( $data );
		} ) );
		self::macro( 'cond', curryN( 2, function ( array $conditions, $data ) {
			foreach ( $conditions as $condition ) {
				if ( $condition[0]( $data ) ) {
					return $condition[1]( $data );
				}
			}
		} ) );
		self::macro( 'both', curryN( 3, function ( callable $a, callable $b, $data ) {
			return $a( $data ) && $b( $data );
		} ) );

		self::macro( 'allPass', curryN( 2, function ( array $predicates, $data ) {
			foreach ( $predicates as $predicate ) {
				if ( ! $predicate( $data ) ) {
					return false;
				}
			}

			return true;
		} ) );

		self::macro( 'anyPass', curryN( 2, function ( array $predicates, $data ) {
			foreach ( $predicates as $predicate ) {
				if ( $predicate( $data ) ) {
					return true;
				}
			}

			return false;
		} ) );

		self::macro( 'complement', curryN( 1, function ( $fn ) {
			return pipe( $fn, self::not() );
		} ) );

		self::macro( 'defaultTo', curryN( 2, function ( $default, $v ) {
			return is_null( $v ) ? $default : $v;
		} ) );

		self::macro( 'either', curryN( 3, function ( callable $a, callable $b, $data ) {
			return $a( $data ) || $b( $data );
		} ) );

		self::macro( 'until', curryN( 3, function ( $predicate, $transform, $data ) {
			while ( ! $predicate( $data ) ) {
				$data = $transform( $data );
			}

			return $data;
		} ) );

		self::macro( 'propSatisfies', curryN( 3, function ( $predicate, $prop, $data ) {
			return $predicate( Obj::prop( $prop, $data ) );
		} ) );

		self::macro( 'isArray', curryN( 1, function( $a ) {
			return is_array( $a );
		}));

		self::macro( 'isMappable', curryN( 1, function( $a ) {
			return self::isArray( $a ) || ( is_object( $a ) && method_exists( $a, 'map' ) );
		}));

		self::macro( 'isEmpty', curryN( 1, function ( $arg ) {
			if ( is_array( $arg ) || $arg instanceof \Countable ) {
				return count( $arg ) === 0;
			}

			return empty( $arg );
		} ) );

		self::macro( 'firstSatisfying', curryN( 3, function ( $predicate, array $conditions, $data ) {
			foreach ( $conditions as $condition ) {
				$res = $condition( $data );
				if ( $predicate( $res ) ) {
					return $res;
				}
			}

			return null;
		} ) );

		self::macro( 'isTruthy', curryN( 1, function ( $data ) {
			return $data instanceof \Countable ? count( $data ) > 0 : (bool) $data;
		} ) );
	}
}

Logic::init();