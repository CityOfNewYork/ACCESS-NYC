<?php

/**
 * @deprecated version 4.3.0   use 'wp-seo-multilingual` plugin instead.
 */
class WPML_WPSEO_Redirection_Old {

	const OPTION = 'wpseo-premium-redirects-base';

	/**
	 * @return bool
	 */
	function is_redirection() {
		$redirections = $this->get_all_redirections();
		if ( is_array( $redirections ) ) {

			// Use same logic as WPSEO_Redirect_Util::strip_base_url_path_from_url
			$url = trim( $_SERVER['REQUEST_URI'], '/' );
			$base_url_path = ltrim( wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
			if ( stripos( trailingslashit( $url ), trailingslashit( $base_url_path ) ) == 0 ) {
				$url = substr( $url, strlen( $base_url_path ) );
			}

			foreach ( $redirections as $redirection ) {
				if ( $redirection['origin'] === $url || '/' . $redirection['origin'] === $url ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	private function get_all_redirections() {
		return get_option( self::OPTION );
	}
}