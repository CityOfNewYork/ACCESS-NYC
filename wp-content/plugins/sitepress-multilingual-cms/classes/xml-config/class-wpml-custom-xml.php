<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Custom_XML implements WPML_WP_Option {
	const OPTION_KEY = 'wpml-tm-custom-xml';

	/**
	 * @return string
	 */
	function get() {
		return get_option( self::OPTION_KEY, '' );
	}

	function set( $value ) {
		update_option( self::OPTION_KEY, $value );
	}
}
