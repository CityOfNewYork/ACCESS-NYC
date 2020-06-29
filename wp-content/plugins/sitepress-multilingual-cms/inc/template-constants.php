<?php
/**
 * Part of SitePress class definition.
 *
 * @package WPML\Core
 */

if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
	define( 'ICL_LANGUAGE_CODE', $this->this_lang );
}

$language_details = $this->get_language_details( ICL_LANGUAGE_CODE );

if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
	$display_name = isset( $language_details['display_name'] ) ? $language_details['display_name'] : null;
	define( 'ICL_LANGUAGE_NAME', $display_name );
}

if ( ! defined( 'ICL_LANGUAGE_NAME_EN' ) ) {
	$english_name = isset( $language_details['english_name'] ) ? $language_details['english_name'] : null;
	define( 'ICL_LANGUAGE_NAME_EN', $english_name );
}
