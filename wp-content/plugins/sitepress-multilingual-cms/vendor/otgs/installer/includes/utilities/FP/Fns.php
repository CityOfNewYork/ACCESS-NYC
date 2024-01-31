<?php

namespace OTGS\Installer\FP;

use OTGS\Installer\Collect\Support\Macroable;

/**
 * @method static callable|mixed value(mixed ...$data) - Curried :: a|(*→a) → a
 */
class Fns
{

	use Macroable;

	const __ = '__CURRIED_PLACEHOLDER__';

	/**
	 * @return void
	 */
	public static function init()
	{
		self::macro('value', curryN(1, function ($value) {
			return is_callable($value) ? $value() : $value;
		}));
	}
}

Fns::init();
