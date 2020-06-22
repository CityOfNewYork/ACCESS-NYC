<?php

namespace WPML\Compatibility\FusionBuilder;

use WPML\Compatibility\BaseDynamicContent;

class DynamicContent extends BaseDynamicContent {

	/** @var array */
	protected $positions = [ 'before', 'after', 'fallback' ];

	/**
	 * Sets $positions dynamic content to be translatable.
	 *
	 * @param string $string   The decoded string so far.
	 * @param string $encoding The encoding used.
	 *
	 * @return string|array
	 */
	public function decode_dynamic_content( $string, $encoding ) {
		if ( ! $string ) {
			return $string;
		}

		if ( $this->is_dynamic_content( $string ) ) {
			$field = $this->decode_field( $string );

			$decodedContent = [
				'element-content' => [
					'value'     => $string,
					'translate' => false,
				],
			];

			foreach ( $this->positions as $position ) {
				if ( ! empty( $field[ $position ] ) ) {
					$decodedContent[ $position ] = [
						'value'     => $field[ $position ],
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
		if ( is_array( $string ) && isset( $string['element-content'] ) ) {
			$field = $this->decode_field( $string['element-content'] );

			foreach ( $this->positions as $position ) {
				if ( isset( $string[ $position ] ) ) {
					$field[ $position ] = $string[ $position ];
				}
			}

			return $this->encode_field( $field );
		}

		return $string;
	}

	/**
	 * Check if a certain field contains dynamic content.
	 *
	 * @param string $string The string to check.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content( $string ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return isset( json_decode( base64_decode( $string ), true )['element_content'] );
	}

	/**
	 * Decode a dynamic-content field.
	 *
	 * @param string $string The string to decode.
	 *
	 * @return array
	 */
	protected function decode_field( $string ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return json_decode( base64_decode( $string ), true )['element_content'];
	}

	/**
	 * Encode a dynamic-content field.
	 *
	 * @param array $field The field to encode.
	 *
	 * @return string
	 */
	protected function encode_field( $field ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( wp_json_encode( [ 'element_content' => $field ] ) );
	}
}
