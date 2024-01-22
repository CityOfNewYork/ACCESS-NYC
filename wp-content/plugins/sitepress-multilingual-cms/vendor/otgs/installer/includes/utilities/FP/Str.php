<?php

namespace OTGS\Installer\FP;

use OTGS\Installer\Collect\Support\Macroable;

/**
 * @method static callable|string len( ...$str ) - Curried :: string → int
 */
class Str {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'len', curryN( 1, function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen' ) );
	}
}

Str::init();