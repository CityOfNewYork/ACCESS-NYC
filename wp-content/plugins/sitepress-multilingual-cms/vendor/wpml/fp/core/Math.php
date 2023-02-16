<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|mixed multiply( ...$a, ...$b ) - Curried :: Number → Number → Number
 * @method static callable|mixed divide( ...$a, ...$b ) - Curried :: Number → Number → Number
 * @method static callable|mixed add( ...$a, ...$b ) - Curried :: Number → Number → Number
 * @method static callable|mixed product( ...$array ) - Curried :: [Number] → Number
 */
class Math {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'multiply', curryN( 2, function ( $a, $b ) { return $a * $b; } ) );

		self::macro( 'divide', curryN( 2, function ( $a, $b ) { return $a / $b; } ) );

		self::macro( 'add', curryN( 2, function ( $a, $b ) { return $a + $b; } ) );

		self::macro( 'product', curryN(1, Fns::reduce( self::multiply(), 1 ) ) );
	}
}

Math::init();
