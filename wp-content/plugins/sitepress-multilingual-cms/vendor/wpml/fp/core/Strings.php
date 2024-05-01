<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static string tail( string ...$str ) - Curried :: string->string
 * @method static array split( ...$delimiter, ...$str ) - Curried :: string->string->string
 * @method static callable|array parse( ...$string ) - Curried :: string → array
 * @method static callable|bool includes( ...$needle, ...$str ) - Curried :: string → string → bool
 * @method static callable|string trim( ...$trim, ...$str ) - Curried :: string → string → string
 * @method static callable|string trimPrefix( ...$trim, ...$str ) - Curried :: string → string → string
 *
 * Trims the prefix from the start of the string if the prefix exists
 *
 * ```
 * $trimmed = Str::trimPrefix( 'prefix-', 'prefix-test' );
 * ```
 *
 * @method static callable|string concat( ...$a, ...$b ) - Curried :: string → string → string
 * @method static callable|string sub( ...$start, ...$str ) - Curried :: int → string → string
 * @method static callable|string startsWith( ...$test, ...$str ) - Curried :: string → string → bool
 * @method static callable|string endsWith( ...$test, ...$str ) - Curried :: string → string → bool
 * @method static callable|int pos( ...$test, ...$str ) - Curried :: string → string → int
 * @method static callable|int len( ...$str ) - Curried :: string → int
 * @method static callable|string replace( ...$find, ...$replace, ...$str ) - Curried :: string → string → string → string
 * @method static callable|string pregReplace( ...$pattern, ...$replace, ...$str ) - Curried :: string → string → string → string
 * @method static callable|string match( ...$pattern, ...$str ) - Curried :: string → string → array
 * @method static callable|string matchAll( ...$pattern, ...$str ) - Curried :: string → string → array
 * @method static callable|string wrap( ...$before, ...$after, ...$str ) - Curried :: string → string → string
 * @method static callable|string toUpper( string ...$str ) - Curried :: string → string
 * @method static callable|string toLower( string ...$str ) - Curried :: string → string
 *
 * Wraps a string inside 2 other strings
 *
 * ```
 * $wrapsInDiv = Str::wrap( '<div>', '</div>' );
 * $wrapsInDiv( 'To be wrapped' ); // '<div>To be wrapped</div>'
 * ```
 *
 */
class Str {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'split', curryN( 2, 'explode' ) );

		self::macro( 'parse', curryN( 1, function( $string ) {
			parse_str( $string, $parsedString );
			return $parsedString;
		} ) );

		self::macro( 'trim', curryN( 2, flip( 'trim' ) ) );

		self::macro( 'trimPrefix', curryN( 2, function( $prefix, $str ) {
			return $prefix && self::pos( $prefix, $str ) === 0 ? self::sub( self::len( $prefix ), $str ) : $str;
		} ) );

		self::macro( 'concat', curryN( 2, function ( $a, $b ) {
			return $a . $b;
		} ) );

		self::macro( 'sub', curryN( 2, function( $start, $string ) {
			if ( function_exists( 'mb_substr' ) ) {
				return mb_substr( $string, $start );
			}

			return substr( $string, $start );
		} ) );

		self::macro( 'tail', self::sub( 1 ) );

		self::macro( 'pos', curryN( 2, function( $needle, $haystack ) {
			$haystack = ( is_null( $haystack ) ) ? '' : $haystack;

			if ( function_exists( 'mb_strpos' ) ) {
				return mb_strpos( $haystack, $needle );
			}

			return strpos( $haystack, $needle );
		} ) );

		self::macro( 'startsWith', curryN( 2, pipe( self::pos(), Relation::equals( 0 ) ) ) );

		self::macro( 'endsWith', curryN( 2, function ( $find, $s ) {
			return self::sub( - self::len( $find ), $s ) === $find;
		} ) );

		self::macro( 'includes', curryN( 2, pipe( self::pos(), Logic::complement( Relation::equals( false ) ) ) ) );

		self::macro( 'len', curryN( 1, function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen' ) );


		self::macro( 'replace', curryN( 3, function ( $search, $replace, $subject ) {
			return str_replace( $search, $replace, $subject );
		} ) );

		self::macro( 'pregReplace', curryN( 3, function ( $pattern, $replace, $subject ) {
			return preg_replace( $pattern, $replace, $subject );
		} ) );

		self::macro( 'match', curryN( 2, function ( $pattern, $subject ) {
			$matches = [];

			return preg_match( $pattern, $subject, $matches ) ? $matches : [];
		} ) );

		self::macro( 'matchAll', curryN( 2, function ( $pattern, $subject ) {
			$matches = [];

			return preg_match_all( $pattern, $subject, $matches, PREG_SET_ORDER ) ? $matches : [];
		} ) );

		self::macro( 'wrap', curryN( 3, function ( $before, $after, $string ) {
			return $before . $string . $after;
		} ) );

		self::macro( 'toUpper', curryN( 1, 'strtoupper' ) );

		self::macro( 'toLower', curryN( 1, 'strtolower' ) );
	}

	/**
	 * Truncates a string to a maximum number of bytes keeping multibyte chars integrity.
	 *
	 * @param string $string
	 * @param int $max_bytes
	 * @param int|null $max_characters
	 * @return string
	 */
	public static function truncate_bytes( $string, $max_bytes, $max_characters = null ) {
		if ( $max_characters !== null ) {
			$string = mb_substr( $string, 0, $max_characters );
		} else {
			$string = mb_substr( $string, 0, $max_bytes );
			$max_characters = mb_strlen( $string );
		}

		// If the length of the string ( in bytes ) is still too big, we have to cut at least one more character.
		if ( strlen( $string ) > $max_bytes ) {
			return static::truncate_bytes( $string, $max_bytes, $max_characters - 1 );
		}

		return $string;
	}
}

Str::init();
