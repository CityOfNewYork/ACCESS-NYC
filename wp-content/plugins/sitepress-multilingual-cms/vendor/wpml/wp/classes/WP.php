<?php

namespace WPML\LIB\WP;

use WPML\FP\Either;
use WPML\FP\Logic;

class WordPress {

	/**
	 * Compare the WordPress version.
	 * @param string $operator
	 * @param string $version
	 *
	 * @return bool
	 */
	public static function versionCompare( $operator, $version ) {
		global $wp_version;
		return version_compare( $wp_version, $version, $operator );
	}

	/**
	 * @param mixed $var
	 *
	 * @return \WPML\FP\Either|callable
	 */
	public static function handleError( $var = null ) {
		return call_user_func_array( Logic::ifElse( 'is_wp_error', Either::left(), Either::right() ), func_get_args() );
	}

}
