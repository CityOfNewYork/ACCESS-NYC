<?php

namespace WPML\Language\Detection;

class CookieLanguage {
	/** @var \WPML_Cookie */
	private $cookie;

	/** @var string */
	private $defaultLanguage;

	/**
	 * @param  \WPML_Cookie $cookie
	 * @param  string       $defaultLanguage
	 */
	public function __construct( \WPML_Cookie $cookie, $defaultLanguage ) {
		$this->cookie          = $cookie;
		$this->defaultLanguage = $defaultLanguage;
	}

	/**
	 * @param bool $isBackend
	 *
	 * @return string
	 */
	public function getAjaxCookieName( $isBackend ) {
		return $isBackend ? $this->getBackendCookieName() : $this->getFrontendCookieName();
	}

	public function getBackendCookieName() {
		return 'wp-wpml_current_admin_language_' . md5( $this->get_cookie_domain() );
	}

	public function getFrontendCookieName() {
		return 'wp-wpml_current_language';
	}

	public function get( $cookieName ) {
		global $wpml_language_resolution;

		$cookie_value = $this->cookie->get_cookie( $cookieName );
		$lang         = $cookie_value ? substr( $cookie_value, 0, 10 ) : null;
		$lang         = $wpml_language_resolution->is_language_active( $lang ) ? $lang : $this->defaultLanguage;

		return $lang;
	}


	public function set( $cookieName, $lang_code ) {
		global $sitepress;

		if ( is_user_logged_in() ) {
			if ( ! $this->cookie->headers_sent() ) {
				if ( preg_match(
					'@\.(css|js|png|jpg|gif|jpeg|bmp)@i',
					basename( preg_replace( '@\?.*$@', '', $_SERVER['REQUEST_URI'] ) )
				)
					 || isset( $_POST['icl_ajx_action'] ) || isset( $_POST['_ajax_nonce'] ) || defined( 'DOING_AJAX' )
				) {
					return;
				}

				$current_cookie_value = $this->cookie->get_cookie( $cookieName );
				if ( ! $current_cookie_value || $current_cookie_value !== $lang_code ) {
					$cookie_domain = $this->get_cookie_domain();
					$cookie_path   = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
					$this->cookie->set_cookie(
						$cookieName,
						$lang_code,
						time() + DAY_IN_SECONDS,
						$cookie_path,
						$cookie_domain
					);
				}
			}
		} elseif ( $sitepress->get_setting( \WPML_Cookie_Setting::COOKIE_SETTING_FIELD ) ) {
			$wpml_cookie_scripts = new \WPML_Cookie_Scripts( $cookieName, $sitepress->get_current_language() );
			$wpml_cookie_scripts->add_hooks();
		}

		$_COOKIE[ $cookieName ] = $lang_code;

		do_action( 'wpml_language_cookie_added', $lang_code );
	}

	/**
	 * @return bool|string
	 */
	public function get_cookie_domain() {

		return defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : self::get_server_host_name();
	}

	/**
	 * Returns SERVER_NAME, or HTTP_HOST if the first is not available
	 *
	 * @return string
	 */
	private static function get_server_host_name() {
		$host = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = $_SERVER['HTTP_HOST'];
		} elseif ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$host = $_SERVER['SERVER_NAME'] . self::get_port();
			// Removes standard ports 443 (80 should be already omitted in all cases)
			$host = preg_replace( '@:[443]+([/]?)@', '$1', $host );
		}

		return $host;
	}

	private static function get_port() {
		return isset( $_SERVER['SERVER_PORT'] ) && ! in_array( $_SERVER['SERVER_PORT'], [ 80, 443 ] )
			? ':' . $_SERVER['SERVER_PORT']
			: '';
	}
}
