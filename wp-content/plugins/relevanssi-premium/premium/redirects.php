<?php
/**
 * /premium/redirects.php
 *
 * Handles straight redirects based on keywords.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'template_redirect', 'relevanssi_redirects' );

/**
 * Handles the template redirects.
 *
 * Reads the redirects from the 'relevanssi_redirects' option and performs the
 * redirect if there's a match.
 */
function relevanssi_redirects() {
	global $wp_query;

	$url       = false;
	$redirects = get_option( 'relevanssi_redirects', array() );
	if ( empty( $redirects ) || ! is_array( $redirects ) ) {
		return;
	}
	$query = relevanssi_strtolower( get_search_query( false ) );

	if ( empty( $query ) && function_exists( 'FWP' ) ) {
		$query = relevanssi_get_facetwp_query();
	}

	foreach ( $redirects as $redirect ) {
		if ( ! $redirect || is_string( $redirect ) ) {
			continue;
		}

		if ( $redirect['partial'] ) {
			$match = false;
			if ( stristr( $query, $redirect['query'] ) ) {
				$match = true;
			}
			$pattern = '/^' . str_replace( '/', '\/', $redirect['query'] ) . '$/';
			if ( preg_match( $pattern, $query ) ) {
				$match = true;
			}
			if ( $match ) {
				$url = $redirect['url'];

				$redirect['hits'] = ! empty( $redirect['hits'] ) ? $redirect['hits'] + 1 : 1;
				relevanssi_update_redirect( $redirect );
				break;
			}
		} else {
			$match = false;
			if ( $query === $redirect['query'] ) {
				$match = true;
			}
			$pattern = '/^' . str_replace( '/', '\/', $redirect['query'] ) . '$/';
			if ( preg_match( $pattern, $query ) ) {
				$match = true;
			}
			if ( $match ) {
				$url = $redirect['url'];

				$redirect['hits'] = ! empty( $redirect['hits'] ) ? $redirect['hits'] + 1 : 1;
				relevanssi_update_redirect( $redirect );
				break;
			}
		}
	}

	if ( $wp_query->is_search && ! $url ) {
		if ( empty( $query ) && isset( $redirects['no_terms'] ) ) {
			$url = $redirects['no_terms'];
		} elseif ( 0 === $wp_query->found_posts && isset( $redirects['empty'] ) ) {
			$url = $redirects['empty'];
		}
	}

	if ( $url ) {
		if ( wp_redirect( $url ) ) { // phpcs:ignore WordPress.Security.SafeRedirect
			exit();
		}
	}
}

/**
 * Helper function to update the redirect for the hit counting.
 *
 * Takes the new redirect, finds the old one by the `query` field and replaces
 * the redirect in the option.
 *
 * @param array $redirect The redirect array to be added to the option.
 */
function relevanssi_update_redirect( $redirect ) {
	$redirects = get_option( 'relevanssi_redirects', array() );
	$key       = array_search(
		$redirect['query'],
		array_column( $redirects, 'query' ),
		true
	);

	update_option(
		'relevanssi_redirects',
		array_replace( $redirects, array( $key => $redirect ) )
	);
}

/**
 * Makes relatives URLs absolute and validates all URLs.
 *
 * Uses site_url() to make relative URLs absolute and then passes all URLs
 * through wp_http_validate_url().
 *
 * @see wp_http_validate_url()
 *
 * @param string $value A relative or absolute URL to validate.
 *
 * @return string|false The URL, converted to absolute if necessary, and
 * validated. Returns false on failure.
 */
function relevanssi_validate_url( $value ) {
	if ( 'http' !== substr( $value, 0, 4 ) ) {
		// Relative URL, make absolute.
		if ( '/' !== substr( $value, 0, 1 ) ) {
			$value = '/' . $value;
		}
		$value = site_url() . $value;
	}
	return wp_http_validate_url( $value );
}

/**
 * Reads the redirects from the request array and validates the URLs.
 *
 * All relative URLs are converted to absolute URLs for validation and redirects
 * with both the query and URL parameters are kept.
 *
 * @param array $request The options request array.
 *
 * @return array The redirect array.
 *
 * @since 2.2.3
 */
function relevanssi_process_redirects( $request ) {
	$redirects = array();
	foreach ( $request as $key => $value ) {
		if ( 'redirect_empty_searches' === $key && ! empty( $value ) ) {
			$url = relevanssi_validate_url( $value );
			if ( ! empty( $url ) ) {
				$redirects['empty'] = $url;
			}
		}
		if ( 'redirect_no_terms' === $key && ! empty( $value ) ) {
			$url = relevanssi_validate_url( $value );
			if ( ! empty( $url ) ) {
				$redirects['no_terms'] = $url;
			}
		}
		if ( 'query' !== substr( $key, 0, 5 ) ) {
			continue;
		}
		$suffix  = substr( $key, 5 );
		$query   = stripslashes( relevanssi_strtolower( $value ) );
		$partial = false;
		if ( isset( $request[ 'partial' . $suffix ] ) ) {
			$partial = true;
		}
		$url = null;
		if ( isset( $request[ 'url' . $suffix ] ) ) {
			$url = relevanssi_validate_url( $request[ 'url' . $suffix ] );
		}
		$hits = $request[ 'hits' . $suffix ] ?? 0;
		if ( ! empty( $url ) && ! empty( $query ) ) {
			$redirect    = array(
				'query'   => $query,
				'partial' => $partial,
				'url'     => $url,
				'hits'    => $hits,
			);
			$redirects[] = $redirect;
		}
	}
	return $redirects;
}

/**
 * Gets the search query for FacetWP searches.
 *
 * @return string The search query, empty string if nothing is found.
 *
 * @author Jan Willem Oostendorp
 */
function relevanssi_get_facetwp_query() {
	$query = '';

	if ( ! empty( FWP()->helper->settings['facets'] ) && ! empty( FWP()->request->url_vars ) ) {
		$facet_searches = array();
		$url_vars       = FWP()->request->url_vars;
		foreach ( FWP()->helper->settings['facets'] as $facet ) {
			if ( 'search' === $facet['type'] && 'relevanssi' === $facet['search_engine'] && ! empty( $url_vars[ $facet['name'] ] ) ) {
				$facet_searches = array_merge( $facet_searches, $url_vars[ $facet['name'] ] );
			}
		}

		// If there are multiple search queries we won't even try.
		if ( 1 === count( $facet_searches ) ) {
			$query = $facet_searches[0];
		}
	}

	return strtolower( $query );
}
