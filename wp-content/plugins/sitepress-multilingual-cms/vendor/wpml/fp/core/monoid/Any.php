<?php

namespace WPML\FP\Monoid;

class Any extends Monoid {

	public static function _concat( $a, $b ) {
		return $a || $b;
	}

	public static function mempty() {
		return false;
	}
}
