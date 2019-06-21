<?php
/**
 * Contains the helper functions.
 *
 * Some of the functions where created before dropping support for PHP 5.2 and that's the reason why they are not namespaced.
 *
 * @since 6.0.0 File created.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Get a value from an array based on key.
 *
 * If key is present returns the value, else returns the default value.
 *
 * @since 5.6.0 added `bd` prefix.
 *
 * @param array  $array   Array from which value has to be retrieved.
 * @param string $key     Key, whose value to be retrieved.
 * @param mixed  $default Optional. Default value to be returned, if the key is not found.
 *
 * @return mixed Value if key is present, else the default value.
 */
function bd_array_get( $array, $key, $default = null ) {
	return isset( $array[ $key ] ) ? $array[ $key ] : $default;
}

/**
 * Get a value from an array based on key and convert it into bool.
 *
 * @since 5.6.0 added `bd` prefix.
 *
 * @param array  $array   Array from which value has to be retrieved.
 * @param string $key     Key, whose value to be retrieved.
 * @param bool   $default (Optional) Default value to be returned, if the key is not found.
 *
 * @return bool Boolean converted Value if key is present, else the default value.
 */
function bd_array_get_bool( $array, $key, $default = false ) {
	return bd_to_bool( bd_array_get( $array, $key, $default ) );
}

/**
 * Convert a string value into boolean, based on whether the value "True" or "False" is present.
 *
 * @since 5.5
 *
 * @param string $string String value to compare.
 *
 * @return bool True if string is "True", False otherwise.
 */
function bd_to_bool( $string ) {
	return filter_var( $string, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Check if a string starts with a sub string.
 *
 * Copied from StackOverFlow.
 *
 * @see https://stackoverflow.com/a/834355/24949.
 * @since 6.0.0
 *
 * @param string $haystack Haystack.
 * @param string $needle   Needle.
 *
 * @return bool True if Haystack starts with Needle, False otherwise.
 */
function bd_starts_with( $haystack, $needle ) {
	return ( substr( $haystack, 0, strlen( $needle ) ) === $needle );
}

/**
 * Check if a string ends with a sub string.
 *
 * Copied from StackOverFlow.
 *
 * @see https://stackoverflow.com/a/51491517/24949
 * @since 6.0.0
 *
 * @param string $haystack Haystack.
 * @param string $needle   Needle.
 *
 * @return bool True if Haystack ends with Needle, False otherwise.
 */
function bd_ends_with( $haystack, $needle ) {
	return substr( $haystack, - strlen( $needle ) ) === $needle;
}

/**
 * Check if a string contains another sub string.
 *
 * Copied from StackOverFlow.
 *
 * @see https://stackoverflow.com/a/4366748/24949
 * @since 6.0.0
 *
 * @param string $haystack Haystack.
 * @param string $needle   Needle.
 *
 * @return bool True if Haystack ends with Needle, False otherwise.
 */
function bd_contains( $haystack, $needle ) {
	return strpos( $haystack, $needle ) !== false;
}

/**
 * Get the short class name of an object.
 *
 * Short class name is the name of the class without namespace.
 *
 * @since 6.0.0
 *
 * @param object|string $class_name_or_object Object or Class name.
 *
 * @return string Short class name.
 */
function bd_get_short_class_name( $class_name_or_object ) {
	$class_name = $class_name_or_object;

	if ( is_object( $class_name_or_object ) ) {
		$class_name = get_class( $class_name_or_object );
	}

	$pos = strrpos( $class_name, '\\' );
	if ( false === $pos ) {
		return $class_name;
	}

	return substr( $class_name, $pos + 1 );
}

/**
 * Get GMT Offseted time in Unix Timestamp format.
 *
 * @since 6.0.0
 *
 * @param string $time_string Time string.
 *
 * @return int GMT Offseted time.in Unix Timestamp.
 */
function bd_get_gmt_offseted_time( $time_string ) {
	$gmt_offset = sanitize_text_field( get_option( 'gmt_offset' ) );

	return strtotime( $time_string ) - ( $gmt_offset * HOUR_IN_SECONDS );
}

/**
 * Get the formatted list of allowed mime types.
 * This function was originally defined in the Bulk Delete Attachment addon.
 *
 * @since 5.5
 *
 * @return array List of allowed mime types after formatting
 */
function bd_get_allowed_mime_types() {
	$mime_types = get_allowed_mime_types();
	sort( $mime_types );

	$processed_mime_types        = array();
	$processed_mime_types['all'] = __( 'All mime types', 'bulk-delete' );

	$last_value = '';
	foreach ( $mime_types as $key => $value ) {
		$splitted = explode( '/', $value, 2 );
		$prefix   = $splitted[0];

		if ( '' == $last_value || $prefix != $last_value ) {
			$processed_mime_types[ $prefix ] = __( 'All', 'bulk-delete' ) . ' ' . $prefix;
			$last_value                      = $prefix;
		}

		$processed_mime_types[ $value ] = $value;
	}

	return $processed_mime_types;
}
