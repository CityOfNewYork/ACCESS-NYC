<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static string tail( string ...$str ) - Curried :: string->string
 * @method static array split( ...$delimiter, ...$str ) - Curried :: string->string->string
 * @method static callable|bool includes( ...$needle, ...$str ) - Curried :: string → string → bool
 * @method static callable|string trim( ...$trim, ...$str ) - Curried :: string → string → string
 * @method static callable|string concat( ...$a, ...$b ) - Curried :: string → string → string
 * @method static callable|string sub( ...$start, ...$str ) - Curried :: int → string → string
 * @method static callable|string startsWith( ...$test, ...$str ) - Curried :: string → string → bool
 * @method static callable|string pos( ...$test, ...$str ) - Curried :: string → string → int
 * @method static callable|string len( ...$str ) - Curried :: string → int
 * @method static callable|string replace( ...$find, ...$replace, ...$str ) - Curried :: string → string → string → string
 * @method static callable|string pregReplace( ...$pattern, ...$replace, ...$str ) - Curried :: string → string → string → string
 * @method static callable|string match( ...$pattern, ...$str ) - Curried :: string → string → array
 * @method static callable|string matchAll( ...$pattern, ...$str ) - Curried :: string → string → array
 */
class Str {
	use Macroable;

	public static function init() {

		self::macro( 'split', curryN( 2, 'explode' ) );

		self::macro( 'trim', curryN( 2, flip( 'trim' ) ) );

		self::macro( 'concat', curryN( 2, function ( $a, $b ) {
			return $a . $b;
		} ) );

		self::macro( 'sub', curryN( 2, flip( 'substr' ) ) );

		self::macro( 'tail', self::sub( 1 ));

		self::macro( 'pos', curryN( 2, flip( 'strpos' ) ) );

		self::macro( 'startsWith', curryN( 2, pipe( self::pos(), Relation::equals( 0 ) ) ) );

		self::macro( 'includes', curryN( 2, pipe( self::pos(), Logic::complement( Relation::equals( false ) ) ) ) );

		self::macro( 'len', curryN( 1, 'strlen' ) );

		self::macro( 'replace', curryN( 3, 'str_replace') );

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
	}
}

Str::init();
