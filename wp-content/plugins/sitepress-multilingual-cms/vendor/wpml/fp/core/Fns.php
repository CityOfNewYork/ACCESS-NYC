<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable always( ...$a ) Curried :: a → ( * → a )
 *
 * Returns a function that always returns the given value.
 *
 * ```php
 * $t = Fns::always( 'Tee' );
 * $t(); //=> 'Tee'
 * ```
 *
 * @method static callable converge( ...$convergingFn, ...$branchingFns ) - Curried :: ( ( x1, x2, … ) → z ) → [( ( a, b, … ) → x1 ), ( ( a, b, … ) → x2 ), …] → ( a → b → … → z )
 *
 * Accepts a converging function and a list of branching functions and returns a new function. The arity of the new function is the same as the arity of the longest branching function. When invoked, this new function is applied to some arguments, and each branching function is applied to those same arguments. The results of each branching function are passed as arguments to the converging function to produce the return value.
 *
 * ```php
 * $divide = curryN( 2, function ( $num, $dom ) { return $num / $dom; } );
 * $sum    = function ( Collection $collection ) { return $collection->sum(); };
 * $length = function ( Collection $collection ) { return $collection->count(); };
 *
 * $average = Fns::converge( $divide, [ $sum, $length ] );
 * $this->assertEquals( 4, $average( wpml_collect( [ 1, 2, 3, 4, 5, 6, 7 ] ) ) );
 * ```
 *
 * @method static callable|mixed map( ...$fn, ...$target ) - Curried :: ( a→b )→f a→f b
 *
 * Takes a function and a *functor*, applies the function to each of the functor's values, and returns a functor of the same shape.
 *
 * And array is considered a *functor*
 *
 * Dispatches to the *map* method of the second argument, if present
 *
 * @method static callable|mixed each ( ...$fn, ...$target ) - Curried :: ( a→b )→f a→f b
 * @method static callable|mixed identity( mixed ...$data ) - Curried :: a->a
 * @method static callable|mixed tap( callable  ...$fn, mixed ...$data ) - Curried :: fn->data->data
 * @method static callable|mixed reduce( ...$fn, ...$initial, ...$target ) - Curried :: ( ( a, b ) → a ) → a → [b] → a
 * @method static callable|mixed reduceRight( ...$fn, ...$initial, ...$target ) - Curried :: ( ( a, b ) → a ) → a → [b] → a
 *
 * Takes a function, an initial value and an array and returns the result.
 *
 * The function receives two values, the accumulator and the current value, and should return a result.
 *
 * The array values are passed to the function in the reverse order.
 *
 * ```php
 * $numbers = [ 1, 2, 3, 4, 5, 8, 19 ];
 *
 * $append = function( $acc, $val ) {
 *    $acc[] = $val;
 *    return $acc;
 * };
 *
 * $reducer = Fns::reduceRight( $append, [] );
 * $result = $reducer( $numbers ); // [ 19, 8, 5, 4, 3, 2, 1 ]
 *
 * // Works on collections too.
 * $result = $reducer( wpml_collect( $numbers ) ); // [ 19, 8, 5, 4, 3, 2, 1 ]
 * ```
 *
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
 * @method static callable|mixed memorize( ...$fn ) - Curried :: ( *… → a ) → ( *… → a )
 * @method static callable|mixed once( ...$fn ) - Curried :: ( *… → a ) → ( *… → a )
 * @method static callable|mixed withNamedLock( ...$name, ...$returnFn, ...$fn ) - Curried :: String → ( *… → String ) → ( *… → a ) → ( *… → a )
 *
 * Creates a new function that is *locked* so that it wont be called recursively. Multiple functions can use the same lock so they are blocked from calling each other recursively
 *
 * ```php
 *      $lockName = 'my-lock';
 *      $addOne = Fns::withNamedLock(
 *          $lockName,
 *          Fns::identity(),
 *          function ( $x ) use ( &$addOne ) { return $addOne( $x + 1 ); }
 *      );
 *
 *      $this->assertEquals( 13, $addOne( 12 ), 'Should not recurse' );
 *
 *      $addTwo = Fns::withNamedLock(
 *          $lockName,
 *          Fns::identity(),
 *          function ( $x ) use ( $addOne ) { return pipe( $addOne, $addOne) ( $x ); }
 *      );
 *
 *      $this->assertEquals( 10, $addTwo( 10 ), 'Should return 10 because $addOne is locked by the same name as $addTwo' );
 * ```
 *
 * @method static callable|mixed withoutRecursion( ...$returnFn, ...$fn ) - Curried :: ( *… → String ) → ( *… → a ) → ( *… → a )
 * @method static callable|mixed liftA2( ...$fn, ...$monadA, ...$monadB ) - Curried :: ( a → b → c ) → m a → m b → m c
 * @method static callable|mixed liftA3( ...$fn, ...$monadA, ...$monadB, ...$monadC ) - Curried :: ( a → b → c → d ) → m a → m b → m c → m d
 * @method static callable|mixed liftN( ...$n, ...$fn, ...$monad ) - Curried :: Number->( ( * ) → a ) → ( *m ) → m a
 *
 * @method static callable|mixed until( ...$predicate, ...$fns ) - Curried :: ( b → bool ) → [( a → b )] → a → b
 *
 * Executes consecutive functions until their $predicate($fn(...$args)) is true. When a result fulfils predicate then it is returned.
 *
 * ```
 *       $fns = [
 *         $add(1),
 *         $add(5),
 *         $add(10),
 *         $add(23),
 *      ];
 *
 *      $this->assertSame( 20, Fns::until( Relation::gt( Fns::__, 18 ), $fns )( 10 ) );
 * ```
 *
 */
class Fns {

	use Macroable;

	const __ = '__CURRIED_PLACEHOLDER__';

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'always', function ( $value ) {
			return function () use ( $value ) { return $value; };
		} );

		self::macro( 'converge', curryN( 2, function ( $convergingFn, array $branchingFns ) {
			return function ( $data ) use ( $convergingFn, $branchingFns ) {
				$apply = function ( $fn ) use ( $data ) { return $fn( $data ); };

				return call_user_func_array( $convergingFn, self::map( $apply, $branchingFns ) );
			};
		} ) );

		self::macro( 'map', curryN( 2, function ( $fn, $target ) {
			if ( ! Logic::isMappable( $target ) ) {
				throw( new \InvalidArgumentException( 'target should be an object with map method or an array' ) );
			}

			if ( is_object( $target ) ) {
				return $target->map( $fn );
			} else {
				$keys = array_keys( $target );

				return array_combine( $keys, array_map( $fn, $target, $keys ) );
			}
		} ) );

		self::macro( 'each', curryN( 2, function ( $fn, $target ) {
			return self::map( self::tap( $fn ), $target );
		} ) );

		self::macro( 'identity', curryN( 1, function ( $value ) { return $value; } ) );

		self::macro( 'tap', curryN( 2, function ( $fn, $value ) {
			$fn( $value );

			return $value;
		} ) );

		self::macro( 'reduce', curryN( 3, function ( $fn, $initial, $target ) {
			if ( is_object( $target ) ) {
				return $target->reduce( $fn, $initial );
			}
			if ( is_array( $target ) ) {
				return array_reduce( $target, $fn, $initial );
			}
			throw( new \InvalidArgumentException( 'target should be an object with reduce method or an array' ) );
		} ) );

		self::macro( 'reduceRight', curryN( 3, function ( $fn, $initial, $target ) {
			if ( is_object( $target ) ) {
				return $target->reverse()->reduce( $fn, $initial );
			}
			if ( is_array( $target ) ) {
				return array_reduce( array_reverse( $target ), $fn, $initial );
			}
			throw( new \InvalidArgumentException( 'target should be an object with reduce method or an array' ) );
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
				if ( array_key_exists( $key, $cache ) ) {
					return $cache[ $key ];
				}

				$result        = call_user_func_array( $fn, $args );
				$cache[ $key ] = $result;

				return $result;
			};
		} ) );

		self::macro(
			'memorize',
			self::memorizeWith( gatherArgs( pipe( Fns::map( 'json_encode'), Lst::join('|') ) ) )
		);

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

		self::macro( 'withNamedLock', curryN( 3, function ( $name, $returnFn, $fn ) {
			static $inProgress = [];

			return function () use ( &$inProgress, $name, $returnFn, $fn ) {

				$args = func_get_args();
				if ( Obj::prop( $name, $inProgress ) ) {
					return call_user_func_array( $returnFn, $args );
				}
				$inProgress[ $name ] = true;
				$result              = call_user_func_array( $fn, $args );
				$inProgress[ $name ] = false;

				return $result;
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

		self::macro( 'liftA2', curryN( 3, function ( $fn, $monadA, $monadB ) {
			return $monadA->map( $fn )->ap( $monadB );
		} ) );

		self::macro( 'liftA3', curryN( 4, function ( $fn, $monadA, $monadB, $monadC ) {
			return $monadA->map( $fn )->ap( $monadB )->ap( $monadC );
		} ) );

		self::macro( 'liftN', function ( $n, $fn ) {
			$liftedFn = curryN( $n, function () use ( $n, $fn ) {
				$args   = func_get_args();
				$result = $args[0]->map( curryN( $n, $fn ) );

				return Fns::reduce(
					function ( $result, $monad ) { return $result->ap( $monad ); },
					$result,
					Lst::drop( 1, $args )
				);
			} );

			return call_user_func_array( $liftedFn, Lst::drop( 2, func_get_args() ) );
		} );


		self::macro( 'until', curryN( 3, function ( $predicate, array $fns, ...$args ) {
			foreach ( $fns as $fn ) {
				$result = $fn( ...$args );
				if ( $predicate( $result ) ) {
					return $result;
				}
			}

			return null;
		} ) );
	}

	/**
	 * @return \Closure
	 */
	public static function noop() {
		return function () { };
	}

	/**
	 * Curried function that transforms a Maybe into an Either.
	 *
	 * @param mixed|null $or
	 * @param Maybe|null $maybe
	 *
	 * @return callable|Either
	 */
	public static function maybeToEither( $or = null, $maybe = null ) {
		$toEither = function ( $or, Maybe $maybe ) {
			return self::isJust( $maybe ) ? Either::right( $maybe->getOrElse( null ) ) : Either::left( $or );
		};

		return call_user_func_array( curryN( 2, $toEither ), func_get_args() );
	}
}

Fns::init();
