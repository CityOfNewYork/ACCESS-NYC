<?php

namespace WPML\API;

class Sanitize {
	/**
	 * @param string $value
	 *
	 * @return false|string
	 */
	public static function string( $value ) {
		return is_string( $value ) || is_numeric($value)
			? htmlspecialchars( strip_tags( $value ), ENT_QUOTES ) : false;
	}

	/**
	 * @param string $property
	 * @param array $arr
	 *
	 * @return null|false|string
	 */
	public static function stringProp( $property, $arr ) {
		return isset( $arr[ $property ] ) ? self::string( $arr[ $property ] ) : null;
	}
}