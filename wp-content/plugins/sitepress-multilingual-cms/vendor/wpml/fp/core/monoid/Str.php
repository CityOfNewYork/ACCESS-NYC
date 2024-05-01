<?php

namespace WPML\FP\Monoid;

class Str extends Monoid {
	public static function _concat( $a, $b ) {
		return $a . $b;
	}

	public static function mempty() {
		return '';
	}
}

