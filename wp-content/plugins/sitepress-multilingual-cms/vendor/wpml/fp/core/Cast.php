<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|bool toBool( mixed ...$v ) - Curried :: mixed->bool
 * @method static callable|int toInt( mixed ...$v ) - Curried :: mixed->int
 * @method static callable|string toStr( mixed ...$v ) - Curried :: mixed->string
 * @method static callable|array toArr( mixed ...$v ) - Curried :: mixed->array
 */
class Cast {
	use Macroable;

	public static function init() {
		self::macro( 'toBool', curryN( 1, function ( $v ) { return (bool) $v; } ) );
		self::macro( 'toInt', curryN( 1, function ( $v ) { return intval( $v ); } ) );
		self::macro( 'toStr', curryN( 1, function ( $v ) { return (string) $v; } ) );
		self::macro( 'toArr', curryN( 1, function ( $v ) { return (array) $v; } ) );
	}
}

Cast::init();
