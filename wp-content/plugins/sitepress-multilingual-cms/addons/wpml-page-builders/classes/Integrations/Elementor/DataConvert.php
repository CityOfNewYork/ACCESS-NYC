<?php

namespace WPML\PB\Elementor;

class DataConvert {

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	public static function serialize( array $data ) {
		return wp_slash( wp_json_encode( $data ) );
	}

	/**
	 * @param array|string $data
	 *
	 * @return array
	 */
	public static function unserialize( $data ) {
		if ( self::isElementorArray( $data ) ) {
			return $data;
		}

		$value = is_array( $data ) ? $data[0] : $data;

		if ( self::isElementorArray( $value ) ) {
			return $value;
		}

		return self::unserializeString( $value );
	}

	/**
	 * @param string $string
	 *
	 * @return array
	 */
	private static function unserializeString( $string ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		return is_serialized( $string ) ? unserialize( $string ) : json_decode( $string, true );
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
