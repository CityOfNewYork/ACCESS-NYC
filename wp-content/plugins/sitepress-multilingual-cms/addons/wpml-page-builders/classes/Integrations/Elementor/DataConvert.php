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
		return json_decode( is_array( $data ) ? $data[0] : $data, true );
	}
}
