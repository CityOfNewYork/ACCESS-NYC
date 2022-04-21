<?php

namespace WPML\LIB\WP;

class WPDB {

	/**
	 * It prevents MySQL errors in debug.log.
	 *
	 * @param callable $func
	 *
	 * @return mixed
	 */
	public static function withoutError( callable $func ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$originalSuppressErrors = $wpdb->suppress_errors;
		$wpdb->suppress_errors( true );
		$result = $func();
		$wpdb->suppress_errors( $originalSuppressErrors );
		return $result;
	}
}
