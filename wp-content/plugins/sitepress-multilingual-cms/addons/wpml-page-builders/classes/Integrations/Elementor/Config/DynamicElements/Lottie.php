<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;

class Lottie {

	/**
	 * @return array
	 */
	public static function get() {
		$isLottie        = Relation::propEq( 'widgetType', 'lottie' );
		$lottieLinksLens = Obj::lensPath( [ 'settings', '__dynamic__', 'custom_link' ] );

		return [ $isLottie, $lottieLinksLens, 'popup', 'popup' ];
	}
}
