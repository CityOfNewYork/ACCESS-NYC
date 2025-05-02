<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;


class ContainerPopup {

	/**
	 * @return array
	 */
	public static function get() {
		$isContainerPopup = Relation::propEq( 'elType', 'container' );
	
		$containerLinksLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensPath( [ '__dynamic__', 'link' ] )
		);
		return [ $isContainerPopup, $containerLinksLens, 'popup', 'popup' ];
	}
}
