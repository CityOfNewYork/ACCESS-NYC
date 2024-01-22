<?php

namespace WPML\FP;

use WPML\Collect\Support\Collection;
use WPML\Collect\Support\Traits\Macroable;
use WPML\Collect\Support\Arr;

/**
 * Lst class contains functions for working on ordered arrays indexed with numerical keys
 *
 * @method static callable|array append( mixed ...$newItem, array ...$data ) - Curried :: mixed->array->array
 * @method static callable|array fromPairs( array ...$array ) - Curried :: [[a, b]] → [a => b]
 * @method static callable|array toObj( array ...$array ) - Curried :: array → object
 * @method static callable|array pluck( ...$prop, ...$array ) - Curried :: string → array → array
 * @method static callable|array partition( ...$predicate, ...$target ) - Curried :: ( a → bool ) → [a] → [[a], [a]]
 * @method static callable|array sort( ...$fn, ...$target ) - Curried :: ( ( a, a ) → int|bool ) → [a] → [a]
 * @method static callable|array unfold( ...$fn, ...$seed ) - Curried :: ( a → [b] ) → * → [b]
 * @method static callable|array zip( ...$a, ...$b ) - Curried :: [a] → [b] → [[a, b]]
 * @method static callable|array zipObj( ...$a, ...$b ) - Curried :: [a] → [b] → [a => b]
 * @method static callable|array zipWith( ...$f, ...$a, ...$b ) - Curried :: ( ( a, b ) → c ) → [a] → [b] → [c]
 * @method static callable|string join( ...$glue, ...$array ) - Curried :: string → [a] → string
 * @method static callable|string joinWithCommasAndAnd( ...$array ) - Curried :: [a] → string
 * @method static callable|array concat( ...$a, ...$b ) - Curried :: [a] → [a] → [a]
 * @method static callable|array|null find( ...$predicate, ...$array ) - Curried :: ( a → bool ) → [a] → a | null
 * @method static callable|array flattenToDepth( ...$depth, ...$array ) - Curried :: int → [[a]] → [a]
 * @method static callable|array flatten( ...$array ) - Curried :: [[a]] → [a]
 * @method static callable|bool includes( ...$val, ...$array ) - Curried :: a → [a] → bool
 * @method static callable|bool includesAll( ...$values, ...$array ) - Curried :: [a] → [a] → bool
 *
 * Determines if all the values are in the given array
 *
 * ```
 * $includes10and20 = Lst::includesAll( [ 10, 20 ] );
 *
 * $this->assertTrue( $includes10and20( [ 5, 10, 15, 20 ] ) );
 * $this->assertFalse( $includes10and20( [ 5, 15, 20 ] ) );
 * ```
 * @method static callable|bool nth( ...$n, ...$array ) - Curried :: int → [a] → a | null
 * @method static callable|bool first( ...$array ) - Curried :: [a, b] → a | null
 * @method static callable|bool last( ...$array ) - Curried :: [a, b] → b | null
 * @method static callable|int length( ...$array ) - Curried :: [a] → int
 * @method static callable|array take( ...$n, ...$array ) - Curried :: int → [a] → [a]
 * @method static callable|array takeLast( ...$n, ...$array ) - Curried :: int → [a] → [a]
 * @method static callable|array slice( ...$offset, ...$limit, ...$array ) - Curried :: int → int->[a] → [a]
 * @method static callable|array drop( ...$n, ...$array ) - Curried :: int → [a] → [a]
 * @method static callable|array dropLast( ...$n, ...$array ) - Curried :: int → [a] → [a]
 * @method static callable|array makePair( ...$a, ...$b ) - Curried :: mixed → mixed → array
 * @method static callable|array make ( ...$a ) - Curried :: mixed → array
 * @method static callable|array insert( ...$index, ...$v, ...$array ) - Curried :: int → mixed → array → array
 * @method static callable|array range( ...$from, ...$to )  - Curried :: int → int → array
 * @method static callable|array xprod( ...$a, ...$b ) - Curried :: [a]->[b]->[a, b]
 *
 * Creates a new list out of the two supplied by creating each possible pair from the lists.
 *
 * ```
 * $a              = [ 1, 2, 3 ];
 * $b              = [ 'a', 'b', 'c' ];
 * $expectedResult = [
 *   [ 1, 'a' ], [ 1, 'b' ], [ 1, 'c' ],
 *   [ 2, 'a' ], [ 2, 'b' ], [ 2, 'c' ],
 *   [ 3, 'a' ], [ 3, 'b' ], [ 3, 'c' ],
 * ];
 *
 * $this->assertEquals( $expectedResult, Lst::xprod( $a, $b ) );
 * ```
 * @method static callable|array prepend( ...$val, ...$array ) - Curried:: a → [a] → [a]
 *
 * Returns a new array with the given element at the front, followed by the contents of the list.
 *
 * @method static callable|array reverse( ...$array ) - Curried:: [a] → [a]
 *
 * Returns a new array with the elements reversed.
 *
 */
class Lst {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'append', curryN( 2, function ( $newItem, array $data ) {
			$data[] = $newItem;

			return $data;
		} ) );

		self::macro( 'fromPairs', curryN( 1, function ( array $data ) {
			$fromPair = function ( array $result, array $pair ) {
				$result[ $pair[0] ] = $pair[1];

				return $result;
			};

			return Fns::reduce( $fromPair, [], $data );
		} ) );

		self::macro( 'toObj', curryN( 1, function ( array $data ) { return (object) $data; } ) );

		self::macro( 'pluck', curryN( 2, function ( $prop, $data ) {
			return Fns::map( Obj::prop( $prop ), $data );
		} ) );

		self::macro( 'partition', curryN( 2, function ( $predicate, $data ) {
			return [ Fns::filter( $predicate, $data ), Fns::reject( $predicate, $data ) ];
		} ) );

		self::macro( 'sort', curryN( 2, function ( $compare, $data ) {
			if ( $data instanceof Collection ) {
				return wpml_collect( self::sort( $compare, $data->toArray() ) );
			}
			$intCompare = function ( $a, $b ) use ( $compare ) {
				return (int) $compare( $a, $b );
			};
			usort( $data, $intCompare );

			return $data;
		} ) );

		self::macro( 'unfold', curryN( 2, function ( $fn, $seed ) {
			$result = [];
			do {
				$iteratorResult = $fn( $seed );
				if ( is_array( $iteratorResult ) ) {
					$result[] = $iteratorResult[0];
					$seed     = $iteratorResult[1];
				}
			} while ( $iteratorResult !== false );

			return $result;

		} ) );

		self::macro( 'zip', curryN( 2, function ( $a, $b ) {
			$result = [];
			for ( $i = 0; $i < min( count( $a ), count( $b ) ); $i ++ ) {
				$result[] = [ $a[ $i ], $b[ $i ] ];
			}

			return $result;
		} ) );

		self::macro( 'zipObj', curryN( 2, function ( $a, $b ) {
			$result = [];
			for ( $i = 0; $i < min( count( $a ), count( $b ) ); $i ++ ) {
				$result[ $a[ $i ] ] = $b[ $i ];
			}

			return $result;
		} ) );

		self::macro( 'zipWith', curryN( 3, function ( $fn, $a, $b ) {
			$result = [];
			for ( $i = 0; $i < min( count( $a ), count( $b ) ); $i ++ ) {
				$result[] = $fn( $a[ $i ], $b[ $i ] );
			}

			return $result;
		} ) );

		self::macro( 'join', curryN( 2, 'implode' ) );

		self::macro( 'joinWithCommasAndAnd', curryN( 1, function ( $array ) {
			$last = Lst::last( $array );
			if ( $last ) {
				if ( Lst::length( $array ) > 1 ) {
					return str_replace( '  ', ' ', Lst::join( ', ', Lst::dropLast( 1, $array ) ) . ' ' . __( ' and ', 'sitepress' ) . ' ' . $last );
					// TODO Replace above with following (after 4.5.0 release to get 'and' translated):
					// return Lst::join( ', ', Lst::dropLast( 1, $array ) ) . ' ' . __( 'and', 'sitepress' ) . ' ' . $last;
				} else {
					return $last;
				}
			} else {
				return '';
			}
		} ) );

		self::macro( 'concat', curryN( 2, 'array_merge' ) );

		self::macro( 'find', curryN( 2, function ( $predicate, $array ) {
			foreach ( $array as $value ) {
				if ( $predicate( $value ) ) {
					return $value;
				}
			}

			return null;
		} ) );

		self::macro( 'flattenToDepth', curryN( 2, flip( Arr::class . '::flatten' ) ) );

		self::macro( 'flatten', curryN( 1, Arr::class . '::flatten' ) );

		self::macro( 'includes', curryN( 2, function ( $val, $array ) {
			return in_array( $val, $array, true );
		} ) );

		self::macro( 'includesAll', curryN( 2, function ( $values, $array ) {
			foreach ( $values as $val ) {
				if ( ! in_array( $val, $array, true ) ) {
					return false;
				}
			}
			return true;
		} ) );

		self::macro( 'nth', curryN( 2, function ( $n, $array ) {
			$count = count( $array );
			if ( $n < 0 ) {
				$n += $count;
			}

			return $n >= 0 && $n < $count ? $array[ $n ] : null;

		} ) );

		self::macro( 'first', self::nth( 0 ) );

		self::macro( 'last', self::nth( - 1 ) );

		self::macro( 'length', curryN( 1, 'count' ) );

		self::macro( 'take', curryN( 2, function ( $n, $array ) {
			return array_slice( $array, 0, $n );
		} ) );

		self::macro( 'takeLast', curryN( 2, function ( $n, $array ) {
			return array_slice( $array, - $n, $n );
		} ) );

		self::macro( 'slice', curryN( 3, function ( $offset, $limit, $array ) {
			return array_slice( $array, $offset, $limit );
		} ) );

		self::macro( 'drop', curryN( 2, self::slice( Fns::__, null ) ) );

		self::macro( 'dropLast', curryN( 2, function ( $n, $array ) {
			$len = count( $array );

			return self::take( $n < $len ? $len - $n : 0, $array );
		} ) );

		self::macro( 'makePair', curryN( 2, function ( $a, $b ) {
			return [ $a, $b ];
		} ) );

		self::macro( 'make', curryN( 1, function ( ...$args ) {
			return $args;
		} ) );

		self::macro( 'insert', curryN( 3, function ( $index, $v, $array ) {
			$values = array_values( $array );

			array_splice( $values, $index, 0, [ $v ] );

			return $values;
		} ) );

		self::macro( 'range', curryN( 2, 'range' ) );

		self::macro( 'xprod', curryN( 2, function ( $a, $b ) {
			$result = [];
			foreach ( $a as $el1 ) {
				foreach ( $b as $el2 ) {
					$result[] = [ $el1, $el2 ];
				}
			}

			return $result;
		} ) );

		self::macro( 'prepend', Lst::insert( 0 ) );

		self::macro( 'reverse', curryN( 1, 'array_reverse' ) );
	}

	/**
	 * Curried function that keys the array by the given key
	 *
	 * keyBy :: string -> array -> array
	 *
	 * ```
	 * $data = [
	 *    [ 'x' => 'a', 'y' => 123 ],
	 *    [ 'x' => 'b', 'y' => 456 ],
	 * ];
	 *
	 * Lst::keyBy( 'x', $data );
	 * [
	 *    'a' => [ 'x' => 'a', 'y' => 123 ],
	 *    'b' => [ 'x' => 'b', 'y' => 456 ],
	 * ],
	 * ```
	 *
	 * @param string $key
	 * @param mixed[]  $array
	 *
	 * @return mixed[]|callable
	 */
	public static function keyBy( $key = null, $array = null ) {
		$keyBy = function ( $key, $array ) {
			$apply = Fns::converge( Lst::zipObj(), [ Lst::pluck( $key ), Fns::identity() ] );

			return $apply( $array );
		};

		return call_user_func_array( curryN( 2, $keyBy ), func_get_args() );
	}

	/**
	 * Curried function that wraps each item in array with pair: [$key => $item1]
	 *
	 * keyWith :: string -> array -> array
	 *
	 * ```
	 * $data = [ 1, 2.3, 'some data', - 2, 'a' ];
	 *
	 * Lst::keyWith('myKey', $data);
	 * [ [ 'myKey' => 1 ], [ 'myKey' => 2.3 ], [ 'myKey' => 'some data' ], [ 'myKey' => - 2 ], [ 'myKey' => 'a' ] ]
	 * ```
	 *
	 * @param string $key
	 * @param mixed[] $array
	 *
	 * @return mixed[]|callable
	 */
	public static function keyWith( $key = null, $array = null ) {
		$keyWith = function ( $key, $array ) {
			return Fns::map( function ( $item ) use ( $key ) {
				return [ $key => $item ];
			}, $array );
		};

		return call_user_func_array( curryN( 2, $keyWith ), func_get_args() );
	}

	/**
	 * This method will return the values in the original collection that are not present in the given collection:
	 *
	 * @param array|Collection $array1
	 * @param array|Collection $array2
	 *
	 * @return callable|Collection|array
	 */
	public static function diff( $array1 = null, $array2 = null ) {
		$diff = function( $array1, $array2){
			if ( is_object( $array1)) {
				return $array1->diff($array2);
			} else {
				return array_diff( $array1, $array2 );
			}
		};
		return call_user_func_array( curryN(2, $diff), func_get_args());
	}

	/**
	 * It returns array of $val elements repeated $times times.
	 *
	 * @param mixed $val
	 * @param int $times
	 *
	 * @return callable|array[mixed]
	 */
	public static function repeat( $val = null, $times = null ) {
		$repeat = flip( partial( 'array_fill', 0 ) );

		return call_user_func_array( curryN( 2, $repeat ), func_get_args() );
	}

	/**
	 * @param array|Collection $param
	 *
	 * @return callable|int
	 */
	public static function sum( $param = null ) {
		$sum = function ( $param ) {
			return is_object( $param ) ? $param->sum() : array_sum( $param );
		};

		return call_user_func_array( curryN( 1, $sum ), func_get_args() );
	}
}

Lst::init();
