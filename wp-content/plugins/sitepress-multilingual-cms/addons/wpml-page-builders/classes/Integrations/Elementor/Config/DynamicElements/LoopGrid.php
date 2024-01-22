<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;

class LoopGrid {

	/**
	 * @return array
	 */
	public static function get() {
		$loopIdPath = [ 'settings', 'template_id' ];

		$hasLoop = Logic::allPass( [
			Relation::propEq( 'widgetType', 'loop-grid' ),
			Obj::path( $loopIdPath ),
		] );

		$loopIdLens = Obj::lensPath( $loopIdPath );

		return [ $hasLoop, $loopIdLens ];
	}
}
