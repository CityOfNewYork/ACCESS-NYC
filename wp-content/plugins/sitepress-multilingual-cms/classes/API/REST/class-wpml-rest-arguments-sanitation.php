<?php

/**
 * @author OnTheGo Systems
 *
 * The following method can be used as REST arguments sanitation callback
 */
class WPML_REST_Arguments_Sanitation {

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	static function boolean( $value ) {
		/**
		 * `FILTER_VALIDATE_BOOLEAN` returns `NULL` if not valid, but in all other cases, it sanitizes the value
		 */
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 *@param mixed $value
	 *
	 * @return bool
	 */
	static function integer( $value ) {
		return (int) self::float( $value );
	}

	/**
	 *@param mixed $value
	 *
	 * @return bool
	 */
	static function float( $value ) {
		return (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	/**
	 *@param mixed $value
	 *
	 * @return bool
	 */
	static function string( $value ) {
		return filter_var( $value, FILTER_SANITIZE_STRING );
	}

	/**
	 *@param mixed $value
	 *
	 * @return bool
	 */
	static function url( $value ) {
		return filter_var( $value, FILTER_SANITIZE_URL );
	}

	/**
	 *@param mixed $value
	 *
	 * @return bool
	 */
	static function email( $value ) {
		return filter_var( $value, FILTER_SANITIZE_EMAIL );
	}

	/**
	 *@param mixed $value
	 *
	 * @return array
	 */
	static function array_of_integers( $value ) {
		return array_map( 'intval', $value );
	}
}
