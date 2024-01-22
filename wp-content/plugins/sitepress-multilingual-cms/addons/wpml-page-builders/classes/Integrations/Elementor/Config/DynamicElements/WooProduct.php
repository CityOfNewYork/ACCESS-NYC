<?php

namespace WPML\PB\Elementor\Config\DynamicElements;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Logic;


class WooProduct {

	/**
	 * @param string $widgetName
	 *
	 * @return callable(string): string
	 */
	private static function getConfig( $widgetName ) {
		$widgetConfig = wpml_collect( [
			'title'             => [
				'dynamicKey'    => 'title',
				'widgetType'    => 'heading',
				'shortcodeName' => 'woocommerce-product-title-tag',
			],
			'short-description' => [
				'dynamicKey'    => 'editor',
				'widgetType'    => 'text-editor',
				'shortcodeName' => 'woocommerce-product-short-description-tag',
			],
		] )->get( $widgetName );

		return Obj::prop( Fns::__, $widgetConfig );
	}

	/**
	 * @param string $widget
	 *
	 * @return array
	 */
	public static function get( $widget ) {
		$get = self::getConfig( $widget );

		$widgetPath = [ 'settings', '__dynamic__', $get( 'dynamicKey' ) ];

		// $isWooWidget :: array -> bool
		$isWooWidget = Logic::allPass( [
			Relation::propEq( 'widgetType', $get( 'widgetType' ) ),
			Obj::path( $widgetPath ),
		] );

		// $widgetLens :: callable -> callable -> mixed
		$widgetLens = Obj::lensPath( $widgetPath );

		return [ $isWooWidget, $widgetLens, $get( 'shortcodeName' ), 'product_id' ];
	}
}
