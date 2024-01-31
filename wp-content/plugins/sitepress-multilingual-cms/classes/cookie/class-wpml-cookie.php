<?php

class WPML_Cookie {

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expires
	 * @param string $path
	 * @param string $domain
	 * @param bool $HTTPOnly
	 * @param string|null $sameSite
	 */
	public function set_cookie( $name, $value, $expires, $path, $domain, $HTTPOnly  = false, $sameSite = null ) {
		wp_cache_add_non_persistent_groups( __CLASS__ );

		$entryHash = md5( (string) wp_json_encode( [ $name, $value, $path, $domain, $HTTPOnly, $sameSite ] ) );

		if ( wp_cache_get( $name, __CLASS__ ) !== $entryHash ) {
			$this->handle_cache_plugins( $name );
			if ($sameSite) {
				header(
					'Set-Cookie: ' . rawurlencode( $name ) . '=' . rawurlencode( $value )
					. ( $domain ? '; Domain=' . $domain : '' )
					. ( $expires ? '; expires=' . gmdate( 'D, d-M-Y H:i:s', $expires ) . ' GMT' : '' )
					. ( $path ? '; Path=' . $path : '' )
					. ( $this->is_secure_connection() ? '; Secure' : '')
					. ( $HTTPOnly ? '; HttpOnly' : '' )
					. '; SameSite=' . $sameSite,
					false
				);
			} else {
				setcookie( $name, (string) $value, $expires, $path, $domain, $this->is_secure_connection(), $HTTPOnly );
			}

			wp_cache_set( $name, $entryHash, __CLASS__ );
		}
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_cookie( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			return $_COOKIE[ $name ];
		}
		return '';
	}

	/**
	 * simple wrapper for \headers_sent
	 *
	 * @return bool
	 */
	public function headers_sent() {
		return headers_sent();
	}

	/**
	 * @param string $name
	 */
	private function handle_cache_plugins( $name ) {
		// @todo uncomment or delete when #wpmlcore-5796 is resolved
		// do_action( 'wpsc_add_cookie', $name );
	}

	private function is_secure_connection() {
		if (
			\WPML\FP\Obj::prop( 'HTTPS', $_SERVER ) === 'on' ||
			\WPML\FP\Obj::prop( 'HTTP_X_FORWARDED_PROTO', $_SERVER ) === 'https' ||
			\WPML\FP\Obj::prop( 'HTTP_X_FORWARDED_SSL', $_SERVER ) === 'on'
		) {
			return true;
		}

		return false;
	}
}
