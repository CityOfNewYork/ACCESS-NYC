<?php

use WPML\FP\Str;

class WPML_Encoding_Validation {

	const MINIMUM_STRING_LENGTH = 100;

	/**
	 * Checks if data passed is base64 encoded string and if the length of it is more than or equal to $minimumValidStringLength.
	 * Here we check for the length because we had cases were featured image names are passed in a false positive base64 encoding format.,
	 * and this made the whole job to be blocked from sending to translation, while if a real field is encoded the length of it should be way more than how the image name will be.
	 *
	 * @param string $string
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-553
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1793
	 */
	public function is_base64_with_100_chars_or_more( $string ) {
		if ( (bool) preg_match( '/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string ) === false ) {
			return false;
		}

		$decoded = base64_decode( $string, true );
		if ( $decoded === false ) {
			return false;
		}

		$encoding = mb_detect_encoding( $decoded );
		if ( ! in_array( $encoding, [ 'UTF-8', 'ASCII' ], true ) ) {
			return false;
		}

		return $decoded !== false && base64_encode( $decoded ) === $string && Str::len( $string ) >= self::MINIMUM_STRING_LENGTH;
	}
}
