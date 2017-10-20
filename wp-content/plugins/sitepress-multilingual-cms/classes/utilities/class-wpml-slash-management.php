<?php

class WPML_Slash_Management {

	public function match_trailing_slash_to_reference( $url, $reference_url ) {
		if ( trailingslashit( $reference_url ) === $reference_url && ! $this->has_lang_param( $url ) ) {
			return trailingslashit( $url );
		} else {
			return untrailingslashit( $url );
		}
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	private function has_lang_param( $url ) {
		return strpos( $url, '?lang=' ) !== false || strpos( $url, '&lang=' ) !== false;
	}

	/**
	 * @param string $url
	 * @param string $method
	 *
	 * @return mixed|string
	 */
	public function maybe_user_trailingslashit( $url, $method ) {
		global $wp_rewrite;

		$url_parts = wpml_parse_url( $url );
		$path      = isset( $url_parts['path'] ) ? $url_parts['path'] : '';

		if ( null !== $wp_rewrite ) {
			$path = user_trailingslashit( $path );
		} else {
			$path = 'untrailingslashit' === $method
				? untrailingslashit( $path ) : trailingslashit( $path );
		}

		$url_parts['path'] = $path;

		return ltrim( http_build_url( null, $url_parts ), '/' );
	}
}
