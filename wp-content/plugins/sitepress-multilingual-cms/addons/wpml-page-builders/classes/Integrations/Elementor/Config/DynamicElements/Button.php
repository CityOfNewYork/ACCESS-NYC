<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;

class Button {

	/**
	 * @return array
	 */
	public static function get() {
		$isButton       = Relation::propEq( 'widgetType', 'button' );
		$buttonLinkLens = Obj::lensPath( [ 'settings', '__dynamic__', 'link' ] );

		return [ $isButton, $buttonLinkLens, 'internal-url', 'post_id' ];
	}
}
