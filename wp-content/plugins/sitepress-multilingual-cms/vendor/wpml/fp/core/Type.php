<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|bool isNull( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isBool( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isInt( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isNumeric( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isFloat( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isString( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isScalar( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isArray( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isObject( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isCallable( mixed ...$v ) - Curried :: mixed->bool
 *
 * @method static callable|bool isSerialized( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|bool isJson( mixed ...$v ) - Curried :: mixed->bool
 */
class Type {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		// Built-in PHP functions
		self::macro( 'isNull', curryN( 1, 'is_null' ) );
		self::macro( 'isBool', curryN( 1, 'is_bool' ) );
		self::macro( 'isInt', curryN( 1, 'is_int' ) );
		self::macro( 'isNumeric', curryN( 1, 'is_numeric' ) );
		self::macro( 'isFloat', curryN( 1, 'is_float' ) );
		self::macro( 'isString', curryN( 1, 'is_string' ) );
		self::macro( 'isScalar', curryN( 1, 'is_scalar' ) );
		self::macro( 'isArray', curryN( 1, 'is_array' ) );
		self::macro( 'isObject', curryN( 1, 'is_object' ) );
		self::macro( 'isCallable', curryN( 1, 'is_callable' ) );

		/**
		 * Inspired by WordPress function `is_serialized`.
		 *
		 * @see is_serialized()
		 */
		self::macro( 'isSerialized', curryN( 1, function( $data ) {
			// If it isn't a string, it isn't serialized.
			if ( ! is_string( $data ) ) {
				return false;
			}
			$data = trim( $data );
			if ( 'N;' === $data ) {
				return true;
			}
			if ( strlen( $data ) < 4 ) {
				return false;
			}
			if ( ':' !== $data[1] ) {
				return false;
			}

			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}

			$token = $data[0];
			switch ( $token ) {
				case 's':
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				// Or else fall through.
				case 'a':
				case 'O':
					return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
				case 'b':
				case 'i':
				case 'd':
					return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$/", $data );
			}
			return false;
		} ) );

		/**
		 * Inspired by WP-CLI function.
		 *
		 * @see \WP_CLI\Utils\is_json()
		 */
		self::macro( 'isJson', curryN( 1, function( $value ) {
			if ( ! $value || ! is_string( $value ) ) {
				return false;
			}

			if ( ! in_array( $value[0], [ '{', '[' ], true ) ) {
				return false;
			}

			json_decode( $value, true );

			return json_last_error() === JSON_ERROR_NONE;
		} ) );
	}
}

Type::init();
