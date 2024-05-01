<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;

class FormPopup {

	/**
	 * @return array
	 */
	public static function get() {
		$popupIdPath = [ 'settings', 'popup_action_popup_id' ];

		$isFormWithPopup = Logic::allPass( [
			Relation::propEq( 'widgetType', 'form' ),
			Obj::path( $popupIdPath ),
		] );

		$popupIdLens = Obj::lensPath( $popupIdPath );

		return [ $isFormWithPopup, $popupIdLens ];
	}
}
