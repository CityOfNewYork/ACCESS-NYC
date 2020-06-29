<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|mixed map( ...$fn, ...$target ) - Curried :: ( a→b )→f a→f b
 * @method static callable|mixed each ( ...$fn, ...$target ) - Curried :: ( a→b )→f a→f b
 * @method static callable|mixed identity( mixed ...$data ) - Curried :: a->a
 * @method static callable|mixed tap( callable  ...$fn, mixed ...$data ) - Curried :: fn->data->data
 * @method static callable|mixed always( ...$a, ...$b ) - Curried :: a->b->a
 * @method static callable|mixed reduce( ...$fn, ...$initial, ...$target ) - Curried :: ( ( a, b ) → a ) → a → [b] → a
 * @method static callable|mixed converge( ...$convergingFn, ...$branchingFns, ...$data ) - Curried :: callable->[callable]->mixed->callable
 * @method static callable|mixed filter( ...$predicate, ...$target ) - Curried :: ( a → bool ) → [a] → [a]
 * @method static callable|mixed reject( ...$predicate, ...$target ) - Curried :: ( a → bool ) → [a] → [a]
 * @method static callable|mixed value( mixed ...$data ) - Curried :: a|( *→a ) → a
 * @method static callable|object constructN( ...$argCount, ...$className ) - Curried :: int → string → object
 * @method static callable|int ascend( ...$fn, ...$a, ...$b ) - Curried :: ( a → b ) → a → a → int
 * @method static callable|int descend( ...$fn, ...$a, ...$b ) - Curried :: ( a → b ) → a → a → int
 * @method static callable useWith( ...$fn, ...$transformations ) - Curried :: ( ( x1, x2, … ) → z ) → [( a → x1 ), ( b → x2 ), …] → ( a → b → … → z )
 * @method static callable nthArg( ...$n ) - Curried :: int → *… → *
 * @method static callable|mixed either( ...$f, ...$g, ...$e ) - Curried:: ( a → b ) → ( b → c ) → Either a b → c
 * @method static callable|mixed maybe( ...$v, ...$f, ...$m ) - Curried:: b → ( a → b ) → Maybe a → b
 * @method static callable|bool isRight( ...$e ) - Curried:: e → bool
 * @method static callable|bool isLeft( ...$e ) - Curried:: e → bool
 * @method static callable|bool isJust( ...$m ) - Curried:: e → bool
 * @method static callable|bool isNothing( ...$m ) - Curried:: e → bool
 * @method static callable|mixed T( ...$_ ) - Curried :: _ → bool
 * @method static callable|mixed F( ...$_ ) - Curried :: _ → bool
 * @method static callable|Maybe safe( ...$fn ) - Curried :: ( a → b ) → ( a → Maybe b )
 * @method static callable|object make( ...$className ) - Curried :: string → object
 * @method static callable|object makeN( ...$argCount, ...$className ) - Curried :: int → string → object
 * @method static callable unary( ...$fn ) - Curried:: ( * → b ) → ( a → b )
 * @method static callable|mixed memorizeWith( ...$cacheKeyFn, ...$fn ) - Curried :: ( *… → String ) → ( *… → a ) → ( *… → a )
 * @method static callable|mixed once( ...$fn ) - Curried :: ( *… → a ) → ( *… → a )
 * @method static callable|mixed withoutRecursion( ...$returnFn, ...$fn ) - Curried :: ( *… → String ) → ( *… → a ) → ( *… → a )
 *
 */
class Fns {

	use Macroable;

	const __ = '__CURRIED_PLACEHOLDER__';

	public static function init() {
		self::macro( 'map', curryN( 2, function ( $fn, $target ) {
			if ( is_object( $target ) ) {
				return $target->map( $fn );
			}
			if ( is_array( $target ) ) {
				$keys = array_keys( $target );

				return array_combine( $keys, array_map( $fn, $target, $keys ) );
			}
			throw( new \InvalidArgumentException( 'target should be an object with map method or an array' ) );
		} ) );

		self::macro( 'each', curryN( 2, function ( $fn, $target ) {
			return self::map( self::tap( $fn ), $target );
		} ) );

		self::macro( 'identity', curryN( 1, function ( $value ) { return $value; } ) );

		self::macro( 'tap', curryN( 2, function ( $fn, $value ) {
			$fn( $value );

			return $value;
		} ) );

		self::macro( 'always', curryN( 2, function ( $value, $_ ) { return $value; } ) );

		self::macro( 'reduce', curryN( 3, function ( $fn, $initial, $target ) {
			if ( is_object( $target ) ) {
				return $target->reduce( $fn, $initial );
			}
			if ( is_array( $target ) ) {
				return array_reduce( $target, $fn, $initial );
			}
			throw( new \InvalidArgumentException( 'target should be an object with reduce method or an array' ) );
		} ) );

		self::macro( 'converge', curryN( 3, function ( $convergingFn, array $branchingFns, $data ) {
			$apply = function ( $fn ) use ( $data ) { return $fn( $data ); };

			return call_user_func_array( $convergingFn, self::map( $apply, $branchingFns ) );
		} ) );

		self::macro( 'filter', curryN( 2, function ( $predicate, $target ) {
			if ( is_object( $target ) ) {
				return $target->filter( $predicate );
			}
			if ( is_array( $target ) ) {
				return array_values( array_filter( $target, $predicate ) );
			}
			throw( new \InvalidArgumentException( 'target should be an object with filter method or an array' ) );
		} ) );

		self::macro( 'reject', curryN( 2, function ( $predicate, $target ) {
			return self::filter( pipe( $predicate, Logic::not() ), $target );
		} ) );

		self::macro( 'value', curryN( 1, function ( $value ) {
			return is_callable( $value ) ? $value() : $value;
		} ) );

		self::macro( 'constructN', curryN( 2, function ( $argCount, $className ) {
			$maker = function () use ( $className ) {
				$args = func_get_args();

				return new $className( ...$args );
			};

			return curryN( $argCount, $maker );
		} ) );

		self::macro( 'ascend', curryN( 3, function ( $fn, $a, $b ) {
			$aa = $fn( $a );
			$bb = $fn( $b );

			return $aa < $bb ? - 1 : ( $aa > $bb ? 1 : 0 );
		} ) );

		self::macro( 'descend', curryN( 3, function ( $fn, $a, $b ) {
			return self::ascend( $fn, $b, $a );
		} ) );

		self::macro( 'useWith', curryN( 2, function ( $fn, $transformations ) {
			return curryN( count( $transformations ), function () use ( $fn, $transformations ) {
				$apply = function ( $arg, $transform ) {
					return $transform( $arg );
				};

				$args = Fns::map( spreadArgs( $apply ), Lst::zip( func_get_args(), $transformations ) );

				return $fn( ...$args );
			} );
		} ) );

		self::macro( 'nthArg', curryN( 1, function ( $n ) {
			return function () use ( $n ) {
				return Lst::nth( $n, func_get_args() );
			};
		} ) );

		self::macro( 'either', curryN( 3, function ( callable $f, callable $g, Either $e ) {
			if ( $e instanceof Left ) {
				return $e->orElse( $f )->get();
			}

			return $e->map( $g )->get();
		} ) );

		self::macro( 'maybe', curryN( 3, function ( $v, callable $f, Maybe $m ) {
			if ( $m->isNothing() ) {
				return $v;
			}

			return $m->map( $f )->get();
		} ) );

		self::macro( 'isRight', curryN( 1, function ( $e ) {
			return $e instanceof Right;
		} ) );

		self::macro( 'isLeft', curryN( 1, function ( $e ) {
			return $e instanceof Left;
		} ) );

		self::macro( 'isJust', curryN( 1, function ( $m ) {
			return $m instanceof Just;
		} ) );

		self::macro( 'isNothing', curryN( 1, function ( $m ) {
			return $m instanceof Nothing;
		} ) );

		self::macro( 'T', Fns::always( true ) );

		self::macro( 'F', Fns::always( false ) );

		self::macro( 'safe', curryN( 1, function ( $fn ) {
			return pipe( $fn, Maybe::fromNullable() );
		} ) );

		self::macro( 'make', curryN( 1, function ( $className ) {
			return \WPML\Container\make( $className );
		} ) );

		self::macro( 'makeN', curryN( 2, function ( $argCount, $className ) {
			$maker = spreadArgs( curryN( $argCount, function () use ( $className ) {
				return \WPML\Container\make( $className, func_get_args() );
			} ) );

			return $maker( Lst::drop( 2, func_get_args() ) );
		} ) );

		self::macro( 'unary', curryN( 1, function ( $fn ) {
			return function ( $arg ) use ( $fn ) {
				return $fn( $arg );
			};
		} ) );

		self::macro( 'memorizeWith', curryN( 2, function ( $cacheKeyFn, $fn ) {
			return function () use ( $cacheKeyFn, $fn ) {
				static $cache = [];

				$args = func_get_args();
				$key  = call_user_func_array( $cacheKeyFn, $args );
				if ( isset( $cache[ $key ] ) ) {
					return $cache[ $key ];
				}

				$result        = call_user_func_array( $fn, $args );
				$cache[ $key ] = $result;

				return $result;
			};
		} ) );

		self::macro( 'once', curryN( 1, function ( $fn ) {
			return function () use ( $fn ) {
				static $result = [];
				if ( array_key_exists( 'data', $result ) ) {
					return $result['data'];
				}
				$result['data'] = call_user_func_array( $fn, func_get_args() );

				return $result['data'];
			};
		} ) );

		self::macro( 'withoutRecursion', curryN( 2, function ( $returnFn, $fn ) {
			return function () use ( $returnFn, $fn ) {
				static $inProgress = false;

				$args = func_get_args();
				if ( $inProgress ) {
					return call_user_func_array( $returnFn, $args );
				}
				$inProgress = true;
				$result     = call_user_func_array( $fn, $args );
				$inProgress = false;

				return $result;
			};
		} ) );

	}

	public static function noop() {
		return function () {};
	}
}

Fns::init();
