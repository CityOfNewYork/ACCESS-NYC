<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;

class MegaMenu {

	/**
	 * @return array
	 */
	public static function get() {
		$isMenuItem = Relation::propEq( 'widgetType', 'mega-menu' );

		$itemLinkLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensMappedProp( 'menu_items' ),
			Obj::lensPath( [ '__dynamic__', 'item_link' ] )
		);

		return [ $isMenuItem, $itemLinkLens, 'internal-url', 'post_id' ];
	}
}
