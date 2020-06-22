<?php

namespace WPML\FP;

use WPML\Collect\Support\Collection;
use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Functor\ConstFunctor;
use WPML\FP\Functor\IdentityFunctor;

/**
 * @method static prop( ...$key, ...$obj ) - Curried :: string->Collection|array|object->mixed|null
 * @method static propOr( ...$default, ...$key, ...$obj ) - Curried :: mixed->string->Collection|array|object->mixed|null
 * @method static callable|array props( ...$keys, ...$obj ) - Curried :: [keys] → Collection|array|object → [v]
 * @method static path( ...$path, ...$obj ) - Curried :: array->Collection|array|object->mixed|null
 * @method static callable|mixed pathOr( ...$default, ...$path, ...$obj ) - Curried :: mixed → array → Collection|array|object → mixed
 * @method static assoc( ...$key, ...$value, ...$item ) - Curried :: string->mixed->Collection|array|object->mixed|null
 * @method static assocPath( ...$path, ...$value, ...$item ) - Curried :: array->mixed->Collection|array|object->mixed|null
 * @method static lens( ...$getter, ...$setter ) - Curried :: callable->callable->callable
 * @method static lensProp( ...$prop ) - Curried :: string->callable
 * @method static lensPath( ...$path ) - Curried :: array->callable
 * @method static view( ...$lens, ...$obj ) - Curried :: callable->Collection|array|object->mixed
 * @method static set( ...$lens, ...$value, ...$obj ) - Curried :: callable->mixed->Collection|array|object->mixed
 * @method static over( ...$lens, ...$transformation, ...$obj ) - Curried :: callable->callable->Collection|array|object->mixed
 * @method static pick( ...$props, ...$obj ) - Curried :: array->Collection|array->Collection|array
 * @method static pickAll( ...$props, ...$obj ) - Curried :: array->Collection|array->Collection|array
 * @method static pickBy( ...$predicate, ...$obj ) - Curried :: ( ( v, k ) → bool ) → Collection|array->Collection|array
 * @method static project( ...$props, ...$target ) - Curried :: array->Collection|array->Collection|array
 * @method static where( array $condition ) - Curried :: [string → ( * → bool )] → bool
 * @method static callable|bool has( ...$prop, ...$item ) - Curried :: string → a → bool
 * @method static callable|mixed evolve( ...$transformations, ...$item ) - Curried :: array → array → array
 *
 * @method static callable|array keys( ...$obj ) - Curried :: object|array->array
 *
 * Returns
 *  - keys if argument is an array
 *  - public properties' names if argument is an object
 *  - keys if argument is Collection
 *
 * ```
 * $this->assertEquals( [ 0, 1, 2 ], Obj::keys( [ 'a', 'b', 'c' ] ) );
 * $this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
 *
 * $this->assertEquals( [ 0, 1, 2 ], Obj::keys( \wpml_collect( [ 'a', 'b', 'c' ] ) ) );
 * $this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( \wpml_collect( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) ) );
 *
 * $this->assertEquals( [ 'a', 'b', 'c' ], Obj::keys( (object) [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
 * ```
 *
 * @method static callable|array values( ...$obj ) - Curried :: object|array->array
 *
 * Returns
 *  - values if argument is an array
 *  - public properties' values if argument is an object
 *  - values if argument is Collection
 *
 * ```
 * $this->assertEquals( [ 'a', 'b', 'c' ], Obj::values( [ 'a', 'b', 'c' ] ) );
 * $this->assertEquals( [ 1, 2, 3 ], Obj::values( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
 *
 * $this->assertEquals( [ 'a', 'b', 'c' ], Obj::values( \wpml_collect( [ 'a', 'b', 'c' ] ) ) );
 * $this->assertEquals( [ 1, 2, 3 ], Obj::values( \wpml_collect( [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) ) );
 *
 * $this->assertEquals( [ 1, 2, 3 ], Obj::values( (object) [ 'a' => 1, 'b' => 2, 'c' => 3 ] ) );
 * ```
 */
class Obj {

	use Macroable;

	public static function init() {
		self::macro( 'prop', curryN( 2, function ( $key, $item ) {
			return self::propOr( null, $key, $item );
		} ) );

		self::macro( 'propOr', curryN( 3, function ( $default, $key, $item ) {
			if ( $item instanceof Collection ) {
				return $item->get( $key, $default );
			}
			if ( is_array( $item ) ) {
				return isset( $item[ $key ] ) ? $item[ $key ] : $default;
			}
			if ( is_object( $item ) ) {
				return property_exists( $item, $key ) ? $item->$key : $default;
			}
			if ( is_null( $item ) ) {
				return null;
			}
			throw( new \InvalidArgumentException( 'item should be a Collection or an array or an object' ) );
		} ) );

		self::macro( 'props', curryN( 2, function ( array $keys, $item ) {
			return Fns::map( Obj::prop( Fns::__, $item ), $keys );
		} ) );

		self::macro( 'path', curryN( 2, function ( $path, $item ) {
			return array_reduce( $path, flip( self::prop() ), $item );
		} ) );

		self::macro( 'pathOr', curryN( 3, function ( $default, $path, $item ) {
			$result = self::path( $path, $item );

			return is_null( $result ) ? $default : $result;
		} ) );

		self::macro( 'assocPath', curryN( 3, function ( $path, $val, $item ) {
			$split = [ $item ];
			for ( $i = 0; $i < count( $path ) - 1; $i ++ ) {
				$split[] = self::prop( $path[ $i ], $split[ $i ] );
			}

			$split       = array_reverse( $split );
			$reversePath = array_reverse( $path );

			$split[0] = self::assoc( $reversePath[0], $val, $split[0] );

			for ( $i = 1; $i < count( $reversePath ); $i ++ ) {
				$key         = $reversePath[ $i ];
				$split[ $i ] = self::assoc( $key, $split[ $i - 1 ], $split[ $i ] );
			}

			return array_pop( $split );
		} ) );

		self::macro( 'assoc', curryN( 3, function ( $key, $value, $item ) {
			if ( $item instanceof Collection ) {
				$item = clone $item;

				return $item->put( $key, $value );
			}
			if ( is_array( $item ) ) {
				$item[ $key ] = $value;

				return $item;
			}
			if ( is_object( $item ) ) {
				$item       = clone $item;
				$item->$key = $value;

				return $item;
			}
			if ( is_null( $item ) ) {
				return null;
			}
			throw( new \InvalidArgumentException( 'item should be a Collection or an array or an object' ) );
		} ) );

		self::macro( 'lens', curryN( 2, function ( $getter, $setter ) {
			return function ( $toFunctorFn ) use ( $getter, $setter ) {
				return function ( $target ) use ( $toFunctorFn, $getter, $setter ) {
					$result = $getter( $target );

					return Fns::map( function ( $focus ) use ( $setter, $target ) {
						return $setter( $focus, $target );
					}, $toFunctorFn( $result ) );
				};
			};
		} ) );

		self::macro( 'lensProp', curryN( 1, function ( $prop ) {
			return self::lens( self::prop( $prop ), self::assoc( $prop ) );
		} ) );

		self::macro( 'lensPath', curryN( 1, function ( $path ) {
			return self::lens( self::path( $path ), self::assocPath( $path ) );
		} ) );

		self::macro( 'view', curryN( 2, function ( $lens, $obj ) {
			$view = $lens( [ ConstFunctor::class, 'of' ] );

			return $view( $obj )->get();
		} ) );

		self::macro( 'set', curryN( 3, function ( $lens, $value, $obj ) {
			return self::over( $lens, Fns::always( $value ), $obj );
		} ) );

		self::macro( 'over', curryN( 3, function ( $lens, $transformation, $obj ) {
			$over = $lens( function ( $value ) use ( $transformation ) {
				return IdentityFunctor::of( $transformation( $value ) );
			} );

			return $over( $obj )->get();
		} ) );

		self::macro( 'pick', curryN( 2, function ( array $props, $item ) {
			$find = curryN( 3, function ( $item, $result, $prop ) {
				$value = self::prop( $prop, $item );
				if ( $value ) {
					$result[ $prop ] = $value;
				}

				return $result;
			} );

			$result = Fns::reduce( $find( $item ), [], $props );

			return self::matchType( $result, $item );
		} ) );

		self::macro( 'pickAll', curryN( 2, function ( array $props, $item ) {
			$find = curryN( 3, function ( $item, $result, $prop ) {
				$result[ $prop ] = self::prop( $prop, $item );

				return $result;
			} );

			$result = Fns::reduce( $find( $item ), [], $props );

			return self::matchType( $result, $item );
		} ) );

		self::macro( 'project', curryN( 2, function ( array $props, $items ) {
			return Fns::map( Obj::pick( $props ), $items );
		} ) );

		self::macro( 'where', curryN( 2, function ( array $conditions, $items ) {
			foreach ( $conditions as $prop => $condition ) {
				$filter = pipe( Obj::prop( $prop ), Logic::both( Logic::isNotNull(), $condition ) );
				$items  = Fns::filter( $filter, $items );
			}

			return $items;
		} ) );

		self::macro( 'pickBy', curryN( 2, function ( callable $predicate, $item ) {
			$result = array_filter( self::toArray( $item ), $predicate, ARRAY_FILTER_USE_BOTH );

			return self::matchType( $result, $item );
		} ) );

		self::macro( 'has', curryN( 2, function ( $prop, $item ) {
			if ( $item instanceof Collection ) {
				return $item->has( $prop );
			}
			if ( is_array( $item ) ) {
				return isset( $item[ $prop ] );
			}
			if ( is_object( $item ) ) {
				return property_exists( $item, $prop );
			}
			throw( new \InvalidArgumentException( 'item should be a Collection or an array or an object' ) );
		} ) );

		self::macro( 'evolve', curryN( 2, function ( $transformations, $item ) {
			$temp = self::toArray( $item );

			foreach ( $transformations as $prop => $transformation ) {
				if ( isset( $temp[ $prop ] ) ) {
					if ( is_callable( $transformation ) ) {
						$temp[ $prop ] = $transformation( $temp[ $prop ] );
					} elseif ( is_array( $transformation ) ) {
						$temp[ $prop ] = self::evolve( $transformation, $temp[ $prop ] );
					}
				}
			}

			return self::matchType( $temp, $item );
		} ) );

		self::macro( 'keys', curryN( 1, function ( $obj ) {
			if ( is_array( $obj ) ) {
				return array_keys( $obj );
			} elseif ( $obj instanceof Collection ) {
				return $obj->keys()->toArray();
			} elseif ( is_object( $obj ) ) {
				return array_keys( get_object_vars( $obj ) );
			}

			throw new \InvalidArgumentException( 'obj should be either array or object' );
		} ) );

		self::macro( 'values', curryN( 1, function ( $obj ) {
			if ( is_array( $obj ) ) {
				return array_values( $obj );
			} elseif ( $obj instanceof Collection ) {
				return $obj->values()->toArray();
			} elseif ( is_object( $obj ) ) {
				return array_values( get_object_vars( $obj ) );
			}

			throw new \InvalidArgumentException( 'obj should be either array or object' );
		} ) );
	}

	private static function matchType( $item, $reference ) {
		if ( $reference instanceof Collection ) {
			return wpml_collect( $item );
		}
		if ( is_object( $reference ) ) {
			return (object) $item;
		}

		return $item;
	}

	private static function toArray( $item ) {
		$temp = $item;
		if ( $temp instanceof Collection ) {
			$temp = $temp->toArray();
		}
		if ( is_object( $temp ) ) {
			$temp = (array) $temp;
		}

		return $temp;
	}
}

Obj::init();
