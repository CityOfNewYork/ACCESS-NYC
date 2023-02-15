<?php

/**
 * Class WPML_Language_Per_Domain_SSO
 */
class WPML_Language_Per_Domain_SSO {

	const SSO_NONCE = 'wpml_sso';

	const TRANSIENT_SSO_STARTED   = 'wpml_sso_started';
	const TRANSIENT_DOMAIN        = 'wpml_sso_domain_';
	const TRANSIENT_USER          = 'wpml_sso_user_';
	const TRANSIENT_SESSION_TOKEN = 'wpml_sso_session_';

	const IFRAME_USER_TOKEN_KEY            = 'wpml_sso_token';
	const IFRAME_USER_TOKEN_KEY_FOR_DOMAIN = 'wpml_sso_token_domain';
	const IFRAME_DOMAIN_HASH_KEY           = 'wpml_sso_iframe_hash';
	const IFRAME_USER_STATUS_KEY           = 'wpml_sso_user_status';

	const SSO_TIMEOUT = MINUTE_IN_SECONDS;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_PHP_Functions $php_functions */
	private $php_functions;

	/** @var WPML_Cookie  */
	private $wpml_cookie;

	/** @var string */
	private $site_url;

	/** @var array */
	private $domains;

	/** @var int $current_user_id */
	private $current_user_id;

	public function __construct( SitePress $sitepress, WPML_PHP_Functions $php_functions, WPML_Cookie $wpml_cookie ) {
		$this->sitepress     = $sitepress;
		$this->php_functions = $php_functions;
		$this->wpml_cookie = $wpml_cookie;
		$this->site_url      = $this->sitepress->convert_url( get_home_url(), $this->sitepress->get_default_language() );
		$this->domains       = $this->get_domains();
	}

	public function init_hooks() {

		if ( $this->is_sso_started() ) {
			add_action( 'init', [ $this, 'init_action' ] );

			add_action( 'wp_footer', [ $this, 'add_iframes_to_footer' ] );
			add_action( 'admin_footer', [ $this, 'add_iframes_to_footer' ] );
			add_action( 'login_footer', [ $this, 'add_iframes_to_footer' ] );
		}

		add_action( 'wp_login', [ $this, 'wp_login_action' ], 10, 2 );
		add_filter( 'logout_redirect', [ $this, 'add_redirect_user_token' ], 10, 3 );
	}

	public function init_action() {
		$this->send_headers();
		$this->set_current_user_id();
		if ( $this->is_iframe_request() ) {
			$this->process_iframe_request();
		}
	}

	/**
	 * @param string  $user_login
	 * @param WP_User $user
	 */
	public function wp_login_action( $user_login, WP_User $user ) {
		$this->init_sso_transients( (int) $user->ID );
	}

	/**
	 * @param string           $redirect_to
	 * @param string           $requested_redirect_to
	 * @param WP_User|WP_Error $user
	 *
	 * @return string
	 */
	public function add_redirect_user_token( $redirect_to, $requested_redirect_to, $user ) {
		if ( ! is_wp_error( $user ) && ! $this->is_sso_started() ) {
			$this->init_sso_transients( (int) $user->ID );

			return add_query_arg( self::IFRAME_USER_TOKEN_KEY, $this->create_user_token( $user->ID ), $redirect_to );
		}

		return $redirect_to;
	}

	public function add_iframes_to_footer() {
		$is_user_logged_in = is_user_logged_in();

		if ( $is_user_logged_in && $this->is_sso_started() ) {
			$this->save_session_token( wp_get_session_token(), $this->current_user_id );
		}

		foreach ( $this->domains as $domain ) {
			if ( $domain !== $this->get_current_domain() && $this->is_sso_started_for_domain( $domain ) ) {

				$iframe_url = add_query_arg(
					[
						self::IFRAME_DOMAIN_HASH_KEY => $this->get_hash( $domain ),
						self::IFRAME_USER_STATUS_KEY => $is_user_logged_in ? 'wpml_user_signed_in' : 'wpml_user_signed_out',
						self::IFRAME_USER_TOKEN_KEY_FOR_DOMAIN => $this->create_user_token_for_domains( $this->current_user_id ),
					],
					trailingslashit( $domain )
				);
				?>
				<iframe class="wpml_iframe" style="display:none" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
				<?php
			}
		}
	}

	private function send_headers() {
		header( sprintf( 'Content-Security-Policy: frame-ancestors %s', implode( ' ', $this->domains ) ) );
	}

	/** @param int $user_id */
	private function set_current_user_id( $user_id = null ) {
		if ( $user_id ) {
			$this->current_user_id = $user_id;
		} else {
			$this->current_user_id = $this->get_user_id_from_token() ?: get_current_user_id();
		}
	}

	private function process_iframe_request() {
		if ( $this->validate_user_sign_request() ) {
			nocache_headers();
			wp_clear_auth_cookie();

			if ( $_GET[ self::IFRAME_USER_STATUS_KEY ] == 'wpml_user_signed_in' ) {
				$user = get_user_by( 'id', $this->current_user_id );

				if ( $user !== false ) {
					wp_set_current_user( $this->current_user_id );

					if ( is_ssl() ) {
						$this->set_auth_cookie(
							$this->current_user_id,
							$this->get_session_token( $this->current_user_id )
						);
					} else {
						wp_set_auth_cookie(
							$this->current_user_id,
							false,
							'',
							$this->get_session_token( $this->current_user_id )
						);
					}
					do_action( 'wp_login', $user->user_login, $user );
				}
			} else {
				$sessions = WP_Session_Tokens::get_instance( $this->current_user_id );
				$sessions->destroy_all();
			}
			$this->finish_sso_for_domain( $this->get_current_domain() );
		}

		$this->php_functions->exit_php();
	}

	/** @return bool */
	private function validate_user_sign_request() {
		return isset( $_GET[ self::IFRAME_USER_STATUS_KEY ] )
			   && $this->is_sso_started_for_domain( $this->get_current_domain() );
	}

	/** @return int */
	private function get_user_id_from_token() {
		$user_id = 0;

		if ( isset( $_GET[ self::IFRAME_USER_TOKEN_KEY ] ) ) {
			$transient_key = $this->create_transient_key(
				self::TRANSIENT_USER,
				null,
				filter_var( $_GET[ self::IFRAME_USER_TOKEN_KEY ], FILTER_SANITIZE_STRING )
			);
			$user_id       = (int) get_transient( $transient_key );
			delete_transient( $transient_key );
		} elseif ( isset( $_GET[ self::IFRAME_USER_TOKEN_KEY_FOR_DOMAIN ] ) ) {
			$transient_key = $this->create_transient_key(
				self::TRANSIENT_USER,
				$this->get_current_domain(),
				filter_var( $_GET[ self::IFRAME_USER_TOKEN_KEY_FOR_DOMAIN ], FILTER_SANITIZE_STRING )
			);
			$user_id       = (int) get_transient( $transient_key );
			delete_transient( $transient_key );
		}

		return $user_id;
	}

	/**
	 * @param int $user_id
	 */
	private function init_sso_transients( $user_id ) {
		set_transient( self::TRANSIENT_SSO_STARTED, true, self::SSO_TIMEOUT );

		foreach ( $this->domains as $domain ) {
			if ( $this->get_current_domain() !== $domain ) {
				set_transient(
					$this->create_transient_key( self::TRANSIENT_DOMAIN, $domain, $user_id ),
					$this->get_hash( $domain ),
					self::SSO_TIMEOUT
				);
			}
		}
	}

	/**
	 * @param string $domain
	 */
	private function finish_sso_for_domain( $domain ) {
		delete_transient(
			$this->create_transient_key(
				self::TRANSIENT_DOMAIN,
				$domain,
				$this->current_user_id
			)
		);
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function is_sso_started_for_domain( $domain ) {
		return (bool) get_transient(
			$this->create_transient_key(
				self::TRANSIENT_DOMAIN,
				$domain,
				$this->current_user_id
			)
		);
	}

	/**
	 * @return string
	 */
	private function get_current_domain() {
		$host = '';

		if ( array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
			$host = (string) $_SERVER['HTTP_HOST'];
		}

		return $this->get_current_protocol() . $host;
	}

	/**
	 * @return string
	 */
	private function get_current_protocol() {
		return is_ssl() ? 'https://' : 'http://';
	}

	/**
	 * @return array
	 */
	private function get_domains() {
		$domains = $this->sitepress->get_setting( 'language_domains', array() );

		$active_codes = array_keys( $this->sitepress->get_active_languages() );
		$sso_domains  = array( $this->site_url );

		foreach ( $domains as $language_code => $domain ) {
			if ( in_array( $language_code, $active_codes ) ) {
				$sso_domains[] = $this->get_current_protocol() . $domain;
			}
		}

		return $sso_domains;
	}

	/**
	 * @return bool
	 */
	private function is_iframe_request() {
		return isset( $_GET[ self::IFRAME_DOMAIN_HASH_KEY ] )
			   && ! wpml_is_ajax()
			   && $this->is_sso_started_for_domain( $this->get_current_domain() )
			   && $this->get_hash( $this->get_current_domain() ) === $_GET[ self::IFRAME_DOMAIN_HASH_KEY ];
	}

	/**
	 * @return bool
	 */
	private function is_sso_started() {
		return (bool) get_transient( self::TRANSIENT_SSO_STARTED );
	}

	/**
	 * @param int $user_id
	 *
	 * @return string
	 */
	private function create_user_token( $user_id ) {
		$token = wp_create_nonce( self::SSO_NONCE );
		set_transient(
			$this->create_transient_key( self::TRANSIENT_USER, null, $token ),
			$user_id,
			self::SSO_TIMEOUT
		);

		return $token;
	}

	/**
	 * @param int $user_id
	 *
	 * @return bool|string
	 */
	private function create_user_token_for_domains( $user_id ) {
		$token = wp_create_nonce( self::SSO_NONCE );
		foreach ( $this->domains as $domain ) {
			if ( $this->get_current_domain() !== $domain ) {
				set_transient(
					$this->create_transient_key( self::TRANSIENT_USER, $domain, $token ),
					$user_id,
					self::SSO_TIMEOUT
				);
			}
		}

		return $token;
	}

	/**
	 * @param string $session_token
	 * @param int    $user_id
	 */
	private function save_session_token( $session_token, $user_id ) {
		set_transient(
			$this->create_transient_key( self::TRANSIENT_SESSION_TOKEN, null, $user_id ),
			$session_token,
			self::SSO_TIMEOUT
		);
	}

	/**
	 * @param int $user_id
	 *
	 * @return string
	 */
	private function get_session_token( $user_id ) {
		return (string) get_transient( $this->create_transient_key( self::TRANSIENT_SESSION_TOKEN, null, $user_id ) );
	}

	/**
	 * @param string      $prefix
	 * @param string|null $domain
	 * @param string|null $token
	 *
	 * @return string
	 */
	private function create_transient_key( $prefix, $domain = null, $token = null ) {
		return $prefix . ( $token !== null ? $token : '' ) . ( $domain ? '_' . $this->get_hash( $domain ) : '' );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	private function get_hash( $value ) {
		return hash( 'sha256', self::SSO_NONCE . $value );
	}


	/**
	 * As the WP doesn't support "SameSite" parameter in cookies, we have to write our own
	 * function for saving authentication cookies to work with iframes.
	 *
	 * @param int $user_id
	 * @param string $token
	 */
	private function set_auth_cookie( $user_id, $token = '' ) {
		$expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, false );
		$expire     = 0;
		$secure     = apply_filters( 'secure_auth_cookie', is_ssl(), $user_id );

		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
			$scheme           = 'secure_auth';
		} else {
			$auth_cookie_name = AUTH_COOKIE;
			$scheme           = 'auth';
		}

		if ( '' === $token ) {
			$manager = WP_Session_Tokens::get_instance( $user_id );
			$token   = $manager->create( $expiration );
		}

		$auth_cookie      = wp_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
		$logged_in_cookie = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

		do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token );
		do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token );

		if ( ! apply_filters( 'send_auth_cookies', true ) ) {
			return;
		}

		$this->wpml_cookie->set_cookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, true, 'None' );
		$this->wpml_cookie->set_cookie( $auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, true, 'None' );
		$this->wpml_cookie->set_cookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, true, 'None' );

		if ( COOKIEPATH != SITECOOKIEPATH ) {
			$this->wpml_cookie->set_cookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, true, 'None' );
		}
	}
}
