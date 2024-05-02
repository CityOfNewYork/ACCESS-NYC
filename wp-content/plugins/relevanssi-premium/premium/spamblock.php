<?php
/**
 * /premium/spamblock.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Runs on plugins_loaded and stops spam search requests based on keywords.
 */
function relevanssi_spamblock() {
	$is_highlight_match = false;
	if ( isset( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$query = $_REQUEST['s']; // phpcs:ignore WordPress.Security.NonceVerification
	} elseif ( isset( $_REQUEST['highlight'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$query              = $_REQUEST['highlight']; // phpcs:ignore WordPress.Security.NonceVerification
		$is_highlight_match = true;
	} else {
		/**
		 * Filters the search URL prefix for spam blocking.
		 *
		 * If the search query is not found in $_REQUEST['s'], the spam block
		 * looks for it in $_SERVER['REQUEST_URI'], in case pretty URLs are
		 * used. Relevanssi assumes the pretty URL prefix is /search/, but in
		 * case it's something else, you can adjust that with this filter.
		 *
		 * @param string The search URL prefix, default '/search/'.
		 */
		$url_prefix = apply_filters( 'relevanssi_search_url_prefix', '/search/' );
		if ( substr( $_SERVER['REQUEST_URI'], '0', strlen( $url_prefix ) ) === $url_prefix ) {
			$query = urldecode( str_replace( $url_prefix, '', $_SERVER['REQUEST_URI'] ) );
		}
	}
	if ( ! isset( $query ) || is_string( $query ) === false ) {
		return;
	}

	$settings = get_option( 'relevanssi_spamblock', array() );
	$keywords = $settings['keywords'] ?? '';
	$regex    = $settings['regex'] ?? '';
	$chinese  = $settings['chinese'] ?? 'off';
	$cyrillic = $settings['cyrillic'] ?? 'off';
	$emoji    = $settings['emoji'] ?? 'off';
	$bots     = $settings['bots'] ?? 'off';

	if ( 'on' === $chinese && relevanssi_string_contains_chinese( $query ) ) {
		http_response_code( 410 );
		exit();
	}

	if ( 'on' === $cyrillic && relevanssi_string_contains_cyrillic( $query ) ) {
		http_response_code( 410 );
		exit();
	}

	if ( 'on' === $emoji && relevanssi_string_contains_emoji( $query ) ) {
		http_response_code( 410 );
		exit();
	}

	if ( 'on' === $bots && ! $is_highlight_match && relevanssi_user_agent_is_bot() ) {
		http_response_code( 410 );
		exit();
	}

	foreach ( explode( "\n", $keywords ) as $keyword ) {
		$keyword = trim( $keyword );
		if ( empty( $keyword ) ) {
			continue;
		}
		if ( false !== relevanssi_stripos( $query, $keyword ) ) {
			http_response_code( 410 );
			exit();
		}
	}
	foreach ( explode( "\n", $regex ) as $pattern ) {
		$pattern = trim( $pattern );
		if ( empty( $pattern ) ) {
			continue;
		}
		if ( 1 === preg_match( '/' . $pattern . '/ui', $query ) ) {
			http_response_code( 410 );
			exit();
		}
	}
}

/**
 * Checks if a string contains Chinese characters.
 *
 * @param string $text The text to check.
 *
 * @return boolean
 */
function relevanssi_string_contains_chinese( string $text ): bool {
	return (bool) preg_match( '/\p{Han}/u', $text );
}

/**
 * Checks if a string contains Cyrillic characters. Uses {Cyr} to check.
 *
 * @param string $text The text to check.
 *
 * @return boolean
 */
function relevanssi_string_contains_cyrillic( string $text ): bool {
	return (bool) preg_match( '/\p{Cyrillic}/u', $text );
}

/**
 * Checks if a string contains emoji characters.
 *
 * @param string $text The text to check.
 *
 * @return boolean
 */
function relevanssi_string_contains_emoji( string $text ): bool {
	$emoji = array(
		'/[\x{1F1E6}-\x{1F1FF}]/u', // Flags.
		'/[\x{1F300}-\x{1F5FF}]/u', // Misc and pictographs.
		'/[\x{1F600}-\x{1F64F}]/u', // Emoticons.
		'/[\x{1F680}-\x{1F6FF}]/u', // Transport and maps.
		'/[\x{1F700}-\x{1F9FF}]/u', // Hotel and misc.
		'/[\x{2300}-\x{23FF}]/u', // Time.
		'/[\x{2600}-\x{26FF}]/u', // Miscellaneous.
		'/[\x{2700}-\x{27BF}]/u', // Dingbats.
	);

	foreach ( $emoji as $pattern ) {
		if ( preg_match( $pattern, $text ) ) {
			return true;
		}
	}

	return false;
}
