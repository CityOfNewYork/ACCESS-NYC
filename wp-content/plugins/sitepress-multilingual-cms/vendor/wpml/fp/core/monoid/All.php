<?php

namespace WPML\FP\Monoid;

class All extends Monoid {

	public static function _concat( $a, $b ) {
		return $a && $b;
	}

	public static function mempty() {
		return true;
	}
}

