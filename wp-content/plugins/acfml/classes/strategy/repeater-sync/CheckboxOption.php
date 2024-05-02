<?php

namespace ACFML\Repeater\Sync;

class CheckboxOption {

	const SYNCHRONISE_WP_OPTION_NAME = 'acfml_synchronise_repeater_fields';

	public static function get() {
		return get_option( self::SYNCHRONISE_WP_OPTION_NAME, [] );
	}

	public static function update( $option ) {
		update_option( self::SYNCHRONISE_WP_OPTION_NAME, $option );
	}
}
