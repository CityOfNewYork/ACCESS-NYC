<?php

namespace WPML\Compatibility\Divi;

class DiviOptionsEncoding implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	const CHARS_ENCODED = [ '%22', '%91', '%93' ];
	const CHARS_DECODED = [ '"', '[', ']' ];
	const DELIMITER     = '_';

	public function add_hooks() {
		add_filter( 'wpml_pb_shortcode_decode', [ $this, 'decode_divi_options' ], 10, 2 );
		add_filter( 'wpml_pb_shortcode_encode', [ $this, 'encode_divi_options' ], 10, 2 );
	}

	public function decode_divi_options( $string, $encoding ) {
		if ( 'divi_options' === $encoding ) {
			$options = str_replace( self::CHARS_ENCODED, self::CHARS_DECODED, $string );
			$options = json_decode( $options, true );
			$string  = [];
			foreach ( $options as $index => $option ) {
				foreach ( $option as $key => $value ) {
					$string[ $key . self::DELIMITER . $index ] = [
						'value'     => $value,
						'translate' => 'value' === $key,
					];
				}
			}
		}

		return $string;
	}

	public function encode_divi_options( $string, $encoding ) {
		if ( 'divi_options' === $encoding ) {
			$output = [];
			foreach ( $string as $combined_key => $value ) {
				$parts                    = explode( self::DELIMITER, $combined_key );
				$index                    = array_pop( $parts );
				$key                      = implode( self::DELIMITER, $parts );
				$output[ $index ][ $key ] = $value;
			}
			$output = wp_json_encode( $output, JSON_UNESCAPED_UNICODE );
			$string = str_replace( self::CHARS_DECODED, self::CHARS_ENCODED, $output );

		}

		return $string;
	}
}
