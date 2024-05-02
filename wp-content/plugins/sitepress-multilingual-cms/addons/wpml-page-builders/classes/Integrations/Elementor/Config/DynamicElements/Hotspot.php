<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;

class Hotspot{

	/**
	 * @return array
	 */
	public static function get() {
		$isHotspot = Relation::propEq( 'widgetType', 'hotspot' );
		
		// $hotspotLinksLens :: callable -> callable -> mixed
		$hotspotLinksLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensMappedProp( 'hotspot' ),
			Obj::lensPath( [ '__dynamic__', 'hotspot_link' ] )
		);

		return [ $isHotspot, $hotspotLinksLens, 'popup', 'popup' ];
	}
}
