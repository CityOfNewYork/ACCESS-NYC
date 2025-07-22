<?php

namespace WPML\PB\Elementor;

class DataConvert {

	/**
	 * @param array|object $data
	 * @param bool         $escape
	 *
	 * @return string
	 */
	public static function serialize( $data, $escape = true ) {
		$data = wp_json_encode( $data );
		if ( $escape ) {
			$data = wp_slash( $data );
		}

		return $data;
	}

	/**
	 * @param array|string $data
	 * @param bool         $associative
	 *
	 * @return array|object|mixed
	 */
	public static function unserialize( $data, $associative = true ) {
		if ( self::isElementorArray( $data ) ) {
			return $data;
		}

		$value = is_array( $data ) ? $data[0] : $data;

		if ( self::isElementorArray( $value ) ) {
			return $value;
		}

		return self::unserializeString( $value, $associative );
	}

	/**
	 * @param string $string
	 * @param bool   $associative
	 *
	 * @return array|object|mixed
	 */
	private static function unserializeString( $string, $associative ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		return is_serialized( $string ) ? unserialize( $string ) : json_decode( $string, $associative );
	}

	/**
	 * @param mixed $data
	 *
	 * @return bool
	 */
	private static function isElementorArray( $data ) {
		return is_array( $data ) && count( $data ) > 0 && isset( $data[0]['id'] );
	}
}
