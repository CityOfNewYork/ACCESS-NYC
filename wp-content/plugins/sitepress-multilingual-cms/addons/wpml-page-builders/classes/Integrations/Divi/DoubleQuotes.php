<?php

namespace WPML\Compatibility\Divi;

/**
 * Divi replaces double quotes with %22 when saving shortcode attributes.
 * ATE needs valid HTML so we temporarily decode the double quotes.
 * When we receive the translation we undo the change.
 *
 * @package WPML\Compatibility\Divi
 */
class DoubleQuotes implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		add_filter( 'wpml_pb_shortcode_decode', [ $this, 'decode' ], -PHP_INT_MAX, 2 );
		add_filter( 'wpml_pb_shortcode_encode', [ $this, 'encode' ], PHP_INT_MAX, 2 );
	}

	/**
	 * @param string $string
	 * @param string $encoding
	 *
	 * @return string
	 */
	public function decode( $string, $encoding ) {
		if ( self::canHaveDoubleQuotes( $string, $encoding ) ) {
			$string = str_replace( '%22', '"', $string );
		}

		return $string;
	}

	/**
	 * @param string $string
	 * @param string $encoding
	 *
	 * @return string
	 */
	public function encode( $string, $encoding ) {
		if ( self::canHaveDoubleQuotes( $string, $encoding ) ) {
			$string = str_replace( '"', '%22', $string );
		}

		return $string;
	}

	/**
	 * @param string $string
	 * @param string $encoding
	 *
	 * @return bool
	 */
	private static function canHaveDoubleQuotes( $string, $encoding ) {
		return is_string( $string ) && 'allow_html_tags' === $encoding;
	}

}
