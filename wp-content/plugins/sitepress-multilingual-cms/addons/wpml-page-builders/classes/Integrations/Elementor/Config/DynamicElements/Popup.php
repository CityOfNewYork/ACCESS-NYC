<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Logic;


class Popup {

	/**
	 * @return array
	 */
	public static function get() {
		$popupPath = [ 'settings', '__dynamic__', 'link' ];

		// $isDynamicLink :: array -> bool
		$isDynamicLink = Logic::allPass( [
			Relation::propEq( 'elType', 'widget' ),
			Obj::path( $popupPath ),
		] );

		$lens = Obj::lensPath( $popupPath );

		return [ $isDynamicLink, $lens, 'popup', 'popup' ];
	}
}
