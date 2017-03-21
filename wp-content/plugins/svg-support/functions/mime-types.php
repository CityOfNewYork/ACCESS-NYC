<?php
/**
 * ADD MIME TYPES
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_filter( 'upload_mimes', 'bodhi_svgs_upload_mimes' );

function bodhi_svgs_upload_mimes($mimes = array()) {

	global $bodhi_svgs_options;

	if( empty( $bodhi_svgs_options['restrict'] ) || current_user_can( 'administrator' ) ) {
		// allow SVG file upload
		$mimes['svg'] = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	} else {
		return $mimes;
	}

}

?>