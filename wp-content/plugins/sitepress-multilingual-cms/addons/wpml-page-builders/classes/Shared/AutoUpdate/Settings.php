<?php

namespace WPML\PB\AutoUpdate;

class Settings {

	/**
	 * This is part of the "Translation Auto-Update" feature
	 * The "Translation Auto-Update" feature will be released in the next major version.
	 * We need a way for users to allow disabling it quickly, if necessary.
	 *
	 * @return bool
	 */
	public static function isEnabled() {
		if ( defined( 'WPML_TRANSLATION_AUTO_UPDATE_ENABLED' ) ) {
			return (bool) constant( 'WPML_TRANSLATION_AUTO_UPDATE_ENABLED' );
		}

		return true;
	}
}
