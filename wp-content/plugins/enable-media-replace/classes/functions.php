<?php
namespace EnableMediaReplace;

// Legacy functions.

/**
 * Maybe remove query string from URL.
 *
 * @param string $url
 *
 * @return string
 */

function emr_maybe_remove_query_string( $url ) {
	$parts = explode( '?', $url );

	return reset( $parts );
}

/**
 * Remove scheme from URL.
 *
 * @param string $url
 *
 * @return string
 */
function emr_remove_scheme( $url ) {
	return preg_replace( '/^(?:http|https):/', '', $url );
}

/**
 * Remove size from filename (image[-100x100].jpeg).
 *
 * @param string $url
 * @param bool   $remove_extension
 *
 * @return string
 */
function emr_remove_size_from_filename( $url, $remove_extension = false ) {
	$url = preg_replace( '/^(\S+)-[0-9]{1,4}x[0-9]{1,4}(\.[a-zA-Z0-9\.]{2,})?/', '$1$2', $url );

	if ( $remove_extension ) {
		$ext = pathinfo( $url, PATHINFO_EXTENSION );
		$url = str_replace( ".$ext", '', $url );
	}

	return $url;
}

/**
 * Strip an image URL down to bare minimum for matching.
 *
 * @param string $url
 *
 * @return string
 */
function emr_get_match_url($url) {
	$url = emr_remove_scheme($url);
	$url = emr_maybe_remove_query_string($url);
	$url = emr_remove_size_from_filename($url, true); // and extension is removed.
	$url = emr_remove_domain_from_filename($url);
	return $url;
}


function emr_remove_domain_from_filename($url) {
	// Holding place for possible future function
	$url = str_replace(emr_remove_scheme(get_bloginfo('url')), '', $url);
	return $url;
	}

/**
 * Build an array of search or replace URLs for given attachment GUID and its metadata.
 *
 * @param string $guid
 * @param array  $metadata
 *
 * @return array
 */
function emr_get_file_urls( $guid, $metadata ) {
	$urls = array();

	$guid = emr_remove_scheme( $guid );
	$guid= emr_remove_domain_from_filename($guid);

	$urls['guid'] = $guid;

	if ( empty( $metadata ) ) {
		return $urls;
	}

	$base_url = dirname( $guid );

	if ( ! empty( $metadata['file'] ) ) {
		$urls['file'] = trailingslashit( $base_url ) . wp_basename( $metadata['file'] );
	}

	if ( ! empty( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $key => $value ) {
			$urls[ $key ] = trailingslashit( $base_url ) . wp_basename( $value['file'] );
		}
	}

	return $urls;
}

/**
 * Ensure new search URLs cover known sizes for old attachment.
 * Falls back to full URL if size not covered (srcset or width/height attributes should compensate).
 *
 * @param array $old
 * @param array $new
 *
 * @return array
 */
function emr_normalize_file_urls( $old, $new ) {
	$result = array();

	if ( empty( $new['guid'] ) ) {
		return $result;
	}

	$guid = $new['guid'];

	foreach ( $old as $key => $value ) {
		$result[ $key ] = empty( $new[ $key ] ) ? $guid : $new[ $key ];
	}

	return $result;
}
