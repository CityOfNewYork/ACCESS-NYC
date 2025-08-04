<?php
namespace Gravity_Forms\Gravity_SMTP\Utils;

class Booliesh {

	public static function get( $value, $default = false ) {
		if ( is_string( $value ) ) {
			$value = strtolower( $value );
		}

		if ( $value === false || $value === 0 || $value === '0' || $value === 'no' || $value === 'off' || $value === 'false' || empty( $value ) ) {
			return false;
		}

		if ( $value === true || $value === 1 || $value === '1' || $value === 'yes' || $value === 'on' || $value === 'true' || ! empty( $value ) ) {
			return true;
		}

		return $default;
	}

}