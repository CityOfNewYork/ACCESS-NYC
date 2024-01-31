<?php

namespace OTGS\Installer\FP;

use OTGS\Installer\Collection;
use OTGS\Installer\NullCollection;
use OTGS\Installer\Collect\Support\Macroable;

/**
 * @method static callable|mixed prop( ...$key, ...$obj ) - Curried :: string->Collection|array|object->mixed|null
 * @method static callable|mixed propOr( ...$default, ...$key, ...$obj ) - Curried :: mixed->string->Collection|array|object->mixed|null
 * @method static callable|mixed path( ...$path, ...$obj ) - Curried :: array->Collection|array|object->mixed|null
 * @method static callable|mixed pathOr( ...$default, ...$path, ...$obj ) - Curried :: mixed → array → Collection|array|object → mixed
 * @method static callable|bool has( ...$prop, ...$item ) - Curried :: string → a → bool
 * @method static callable|bool hasPath( ...$path, ...$item ) - Curried :: array<string> → a → bool
 */
class Obj {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'prop', curryN( 2, function ( $key, $item ) {
			return self::propOr( null, $key, $item );
		} ) );

		self::macro( 'propOr', curryN( 3, function ( $default, $key, $item ) {
			if ( $item instanceof Collection && $item->get( $key ) instanceof NullCollection ) {
				return $default;
			}
			if ( is_array( $item ) ) {
				return array_key_exists( $key, $item ) ? $item[ $key ] : $default;
			}
			if ( is_object( $item ) ) {
				if ( property_exists( $item, $key ) || isset( $item->$key ) ) {
					return $item->$key;
				} elseif ( is_numeric( $key ) ) {
					return self::propOr( $default, $key, (array) $item );
				} else {
					return $default;
				}
			}
			if ( is_null( $item ) ) {
				return null;
			}
			throw( new \InvalidArgumentException( 'item should be a Collection or an array or an object' ) );
		} ) );

		self::macro( 'path', curryN( 2, function ( $path, $item ) {
			return array_reduce( $path, flip( self::prop() ), $item );
		} ) );

		self::macro( 'pathOr', curryN( 3, function ( $default, $path, $item ) {
			$result = Either::of( $item )
			                ->tryCatch( Obj::path( $path ) )
			                ->getOrElse( null );

			return is_null( $result ) ? $default : $result;
		} ) );

		self::macro( 'hasPath', curryN( 2, function ( $path, $item ) {
			$undefinedValue = new Undefined();
			$currentElement = $item;

			foreach ( $path as $pathProp ) {
				$currentElement = Either::of( $currentElement )
				                        ->tryCatch( self::propOr( $undefinedValue, $pathProp ) )
				                        ->getOrElse( $undefinedValue );

				if ( $undefinedValue === $currentElement ) {
					return false;
				}
			}

			return true;
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
	}
}

Obj::init();