<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;

class LoopCarousel {

	/**
	 * @return array
	 */
	public static function get() {
		$loopCarouselIdPath = [ 'settings', 'template_id' ];

		$hasLoopCarousel = Logic::allPass( [
			Relation::propEq( 'widgetType', 'loop-carousel' ),
			Obj::path( $loopCarouselIdPath ),
		] );

		$loopCarouselIdLens = Obj::lensPath( $loopCarouselIdPath );

		return [ $hasLoopCarousel, $loopCarouselIdLens ];
	}
}
