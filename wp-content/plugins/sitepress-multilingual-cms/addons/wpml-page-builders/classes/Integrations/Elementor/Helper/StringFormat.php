<?php

namespace WPML\PB\Elementor\Helper;

use WPML\FP\Obj;
use WPML\FP\Str;

class StringFormat {

	/**
	 * @param array           $settings
	 * @param \WPML_PB_String $string
	 *
	 * @return bool
	 */
	public static function useWpAutoP( $settings, $string ) {
		return 'VISUAL' === $string->get_editor_type() && self::isOneLine( self::getOriginalString( $settings, $string ) );
	}

	/**
	 * @param string $content
	 *
	 * @return bool
	 */
	private static function isOneLine( $content ) {
		return ! Str::includes( PHP_EOL, $content );
	}

	/**
	 * @param array           $settings
	 * @param \WPML_PB_String $string
	 *
	 * @return string
	 */
	private static function getOriginalString( $settings, $string ) {
		if ( 'text-editor' === $settings['widgetType'] ) {
			return Obj::path( [ 'settings', 'editor' ], $settings );
		} elseif ( 'hotspot' === $settings['widgetType'] ) {
			$items = Obj::path( [ 'settings', 'hotspot' ], $settings );
			$found = wpml_collect( $items )->first( function( $item ) use ( $string ) {
				return Str::endsWith( $item['_id'], $string->get_name() );
			});
			return Obj::prop( 'hotspot_tooltip_content', $found );
		}
		return '';
	}
}
