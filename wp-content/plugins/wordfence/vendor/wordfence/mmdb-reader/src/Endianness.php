<?php

namespace Wordfence\MmdbReader;

class Endianness {

	const BIG = 0;
	const LITTLE = 1;

	private static $SYSTEM = null;

	private static function detect() {
		$test = unpack('S', "\x00\x01");
		return $test[1] >> 8;
	}

	public static function get() {
		if (self::$SYSTEM === null)
			self::$SYSTEM = self::detect();
		return self::$SYSTEM;
	}

	public static function isBig() {
		return self::get() === self::BIG;
	}

	public static function isLittle() {
		return self::get() === self::LITTLE;
	}

	public static function convert($value, $source, $target = null) {
		if ($target === null)
			$target = self::get();
		if ($target === $source)
			return $value;
		return strrev($value);
	}

}