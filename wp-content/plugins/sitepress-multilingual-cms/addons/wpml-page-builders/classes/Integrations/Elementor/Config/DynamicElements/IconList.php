<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;


class IconList {

	/**
	 * @return array
	 */
	public static function get() {
		// $isIconList :: array -> bool
		$isIconList = Relation::propEq( 'widgetType', 'icon-list' );

		$iconListLinksLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensMappedProp( 'icon_list' ),
			Obj::lensPath( [ '__dynamic__', 'link' ] )
		);

		return [ $isIconList, $iconListLinksLens, 'popup', 'popup' ];
	}
}
