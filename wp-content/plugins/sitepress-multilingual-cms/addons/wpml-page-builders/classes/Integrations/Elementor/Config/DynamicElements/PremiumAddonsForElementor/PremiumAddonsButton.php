<?php

namespace WPML\PB\Elementor\Config\DynamicElements\PremiumAddonsForElementor;

use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\compose;

class PremiumAddonsButton {

	/**
	 * @return array
	 */
	public static function get() {
		$isButton = Relation::propEq( 'widgetType', 'premium-addon-button' );

		$buttonLinkLens = compose(
			Obj::lensProp( 'settings' ),
			Obj::lensPath( [ '__dynamic__', 'premium_button_link' ] )
		);

		return [ $isButton, $buttonLinkLens, 'popup', 'popup' ];
	}
}
