<?php

namespace WPML\AdminLanguageSwitcher;

class DisableWpLanguageSwitcher implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		add_filter( 'login_display_language_dropdown', '__return_false' );
		add_filter( 'logout_redirect', [ $this, 'removeWPLangFromRedirectUrl' ], 10, 2 );
	}

	/**
	 * @param string $redirect_to
	 * @param string $requested_redirect_to
	 * @return string
	 */
	public function removeWPLangFromRedirectUrl( $redirect_to, $requested_redirect_to ) {
		if ( '' !== $requested_redirect_to ) {
			return $redirect_to;
		}

		if ( strpos( $redirect_to, '?' ) > -1 ) {
			$queryString = substr( $redirect_to, strpos( $redirect_to, '?' ) + 1 );
			parse_str( $queryString, $queryStringParams );
			unset( $queryStringParams['wp_lang'] );
			$redirect_to = str_replace( $queryString, http_build_query( $queryStringParams ), $redirect_to );
		}

		return $redirect_to;
	}
}