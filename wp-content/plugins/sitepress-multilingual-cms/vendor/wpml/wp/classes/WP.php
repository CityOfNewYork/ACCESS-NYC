<?php

namespace WPML\LIB\WP;

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

}
