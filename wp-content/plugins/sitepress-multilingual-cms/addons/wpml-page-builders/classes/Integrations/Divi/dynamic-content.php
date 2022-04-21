<?php

namespace WPML\Compatibility\Divi;

use WPML\Compatibility\BaseDynamicContent;

class DynamicContent extends BaseDynamicContent {

	const ENCODED_CONTENT_START = '@ET-DC@';
	const ENCODED_CONTENT_END   = '@';

	/** @var array */
	protected $positions = [ 'before', 'after' ];

	/**
	 * Sets $positions dynamic content to be translatable.
	 *
	 * @param string $string   The decoded string so far.
	 * @param string $encoding The encoding used.
	 *
	 * @return string|array
	 */
	public function decode_dynamic_content( $string, $encoding ) {
		if ( $this->is_dynamic_content( $string ) ) {
			$field = $this->decode_field( $string );

			$decodedContent = [
				'et-dynamic-content' => [
					'value'     => $string,
					'translate' => false,
				],
			];

			foreach ( $this->positions as $position ) {
				if ( ! empty( $field['settings'][ $position ] ) ) {
					$decodedContent[ $position ] = [
						'value'     => $field['settings'][ $position ],
						'translate' => true,
					];
				}
			}

			return $decodedContent;
		}

		return $string;
	}

	/**
	 * Rebuilds dynamic content with translated strings.
	 *
	 * @param string|array $string   The field array or string.
	 * @param string       $encoding The encoding used.
	 *
	 * @return string
	 */
	public function encode_dynamic_content( $string, $encoding ) {
		if ( is_array( $string ) && isset( $string['et-dynamic-content'] ) ) {
			$field = $this->decode_field( $string['et-dynamic-content'] );

			foreach ( $this->positions as $position ) {
				if ( isset( $string[ $position ] ) ) {
					$field['settings'][ $position ] = $string[ $position ];
				}
			}

			return $this->encode_field( $field );
		}

		return $string;
	}

	/**
	 * Decode a dynamic-content field.
	 *
	 * @param string $string The string to decode.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content( $string ) {
		return substr( $string, 0, strlen( self::ENCODED_CONTENT_START ) ) === self::ENCODED_CONTENT_START;
	}

	/**
	 * Decode a dynamic-content field.
	 *
	 * @param string $string The string to decode.
	 *
	 * @return array
	 */
	protected function decode_field( $string ) {
		$start = strlen( self::ENCODED_CONTENT_START );
		$end   = strlen( self::ENCODED_CONTENT_END );

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return json_decode( base64_decode( substr( $string, $start, -$end ) ), true );
	}

	/**
	 * Encode a dynamic-content field.
	 *
	 * @param array $field The field to encode.
	 *
	 * @return string
	 */
	protected function encode_field( $field ) {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return self::ENCODED_CONTENT_START
			. base64_encode( wp_json_encode( $field ) )
			. self::ENCODED_CONTENT_END;
	}
}
