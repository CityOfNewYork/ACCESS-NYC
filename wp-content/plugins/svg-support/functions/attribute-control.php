<?php
/**
 * Attribute Control
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// filters the img tag class during insertion and returns SVG Support class
function bodhi_svgs_image_class_filter($classes) {

	global $bodhi_svgs_options;

	if ( !empty( $bodhi_svgs_options['css_target'] ) ) {
		$classes = $bodhi_svgs_options['css_target'];
	} else {
		$classes = 'style-svg';
	}
	return $classes;

}
if ( !empty( $bodhi_svgs_options['auto_insert_class'] ) ) {
add_filter('get_image_tag_class', 'bodhi_svgs_image_class_filter');
}

// removes the width and height attributes during insertion of svg
function remove_dimensions_svg( $html='' ) {
	if( preg_match( '/src="(.*[.]svg)"\s/', $html ) ) {
		//$html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
	}
	return str_ireplace( array( " width=\"1\"", " height=\"1\"" ), "", $html );
}
add_filter( 'post_thumbnail_html', 'remove_dimensions_svg', 10 );
add_filter( 'image_send_to_editor', 'remove_dimensions_svg', 10 );
