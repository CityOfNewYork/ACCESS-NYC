<?php

namespace WPML\PB\TranslationJob;

class Labels {

	/**
	 * @param string $string
	 * @param bool   $keepNumerics
	 *
	 * @return string
	 */
	public static function convertToHuman( $string, $keepNumerics = false ) {
		/**
		 * Regexp patterns to remove for generating the human readable label.
		 *
		 * @param string[] $patterns
		 */
		$stripPatterns = apply_filters(
			'wpml_pb_strip_patterns_from_labels',
			[
				'/^core[\/-]+/i',
				'/\-\d+$/',
			]
		);

		if ( $keepNumerics ) {
			$stripPatterns = array_filter( $stripPatterns, function ( $pattern ) {
				return strpos( $pattern, '\-\d+' ) === false;
			});
		}

		$string = preg_replace( $stripPatterns, '', $string );
		$bits   = explode( Groups::PATH_SEPARATOR, $string );
		foreach ( $bits as &$bit ) {
			$bit = preg_replace( $stripPatterns, '', $bit );
			$bit = apply_filters( 'wpml_labelize_string', $bit, 'TranslationJob' );
		}
		return implode( Groups::PATH_SEPARATOR, $bits );
	}

}
