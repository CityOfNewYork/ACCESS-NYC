<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;

class ImageBox {

	/**
	 * @return array
	 */
	public static function get() {
		$isImageBox        = Relation::propEq( 'widgetType', 'image-box' );
		$ImageBoxLinksLens = Obj::lensPath( [ 'settings', '__dynamic__', 'link' ] );

		return [ $isImageBox, $ImageBoxLinksLens, 'internal-url', 'post_id' ];
	}
}