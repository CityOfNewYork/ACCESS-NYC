<?php
/**
 * Register all actions and filters for the plugin
 *
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

/**
 * Bitly authorization and token management.
 *
 * @since      2.6.0
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */
class Wp_Bitly_Auth {

	/**
	 * The logger class.
	 *
	 * @since    2.6.0
	 * @access   protected
	 * @var      class wp_bitly_logger
	 */
	protected $wp_bitly_logger;


	/**
	 * The options class.
	 *
	 * @since    2.6.0
	 * @access   protected
	 * @var      class $wp_bitly_options
	 */
	protected $wp_bitly_options;


	/**
	 * Initialize
	 *
	 * @since    2.6.0
	 */
	public function __construct() {

		$this->wp_bitly_logger  = new Wp_Bitly_Logger();
		$this->wp_bitly_options = new Wp_Bitly_Options();

		add_action( 'wp_ajax_wpbitly_oauth_get_token', array( $this, 'get_token' ) );
		add_action( 'wp_ajax_wpbitly_oauth_disconnect', array( $this, 'disconnect' ) );
	}


	/**
	 * Used to short circuit any shortlink functions if we haven't authenticated to Bitly.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	public function is_authorized() {
		return get_option( WPBITLY_AUTHORIZED, false );
	}


	/**
	 * Set the authorization state for the plugin.
	 *
	 * @since 2.4.0
	 * @param bool $auth Whether the plugin is authorized.
	 */
	public function authorize( $auth = true ) {
		if ( true !== $auth ) {
			$auth = false;
		}

		update_option( WPBITLY_AUTHORIZED, $auth );
	}

	/**
	 * Ajax callback function to disconnect from bitly.
	 *
	 * @since 2.6.0
	 */
	public function disconnect() {
		// Check if user is an administrator.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'status'  => 'error',
						'message' => 'Unauthorized access.',
					)
				)
			);
		}

		$wp_nonce    = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		$valid_nonce = wp_verify_nonce( $wp_nonce, 'bitly_disconnect' );
		if ( ! $valid_nonce ) {
			$this->wp_bitly_logger->wpbitly_debug_log( '', 'Disconnect (Ajax) Failed due to invalid nonce.' );
			wp_die(
				wp_json_encode(
					array(
						'status'  => 'error',
						'message' => 'Invalid Nonce.',
					)
				)
			);
		}

		$this->wp_bitly_logger->wpbitly_debug_log( '', 'Disconnecting (Ajax).' );
		$this->wp_bitly_options->set_option( 'oauth_token', '' );
		$this->wp_bitly_options->set_option( 'oauth_login', '' );

		$this->authorize( false );

		echo wp_json_encode( array( 'status' => 'disconnected' ) );
		exit;
	}

	/**
	 * Ajax callback function to retrieve Bitly Access Token.
	 *
	 * @since 2.6.0
	 */
	public function get_token() {
		// Check if user is an administrator.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'status'  => 'error',
						'message' => 'Unauthorized access.',
					)
				)
			);
		}

		check_ajax_referer( 'wpbitly_settings', 'nonce' );

		if ( ! isset( $_POST['code'] ) || empty( $_POST['code'] ) ) {
			$response = array(
				'status'  => 'error',
				'message' => 'Failed to retrieve authorization code.',
			);
			echo wp_json_encode( $response );
			exit;
		}

		$code = sanitize_text_field( wp_unslash( $_POST['code'] ) );

		$param_arr = array(
			'client_id'    => WPBITLY_OAUTH_CLIENT_ID,
			'code'         => $code,
			'redirect_uri' => WPBITLY_OAUTH_REDIRECT_URI,
		);

		$params = urldecode( http_build_query( $param_arr ) );
		$url    = WPBITLY_BITLY_API . '/oauth/access_token?' . $params;

		$http_response = wp_remote_post(
			$url,
			array(
				'timeout'   => 30,
				'headers'   => array(
					'Accept' => 'application/json',
				),
				'sslverify' => WBBITLY_SSL_VERIFY,
			)
		);

		// Check for an HTTP error.
		if ( is_wp_error( $http_response ) ) {
			$this->wp_bitly_logger->wpbitly_debug_log( $http_response, 'class-wp-bitly-auth.php: HTTP request failed.' );
			$response = array(
				'status'  => 'error',
				'message' => 'HTTP request failed.',
			);
			echo wp_json_encode( $response );
			exit;
		}

		$body = wp_remote_retrieve_body( $http_response );

		$this->wp_bitly_logger->wpbitly_debug_log( $body, 'class-wp-bitly-auth.php: Raw response.' );

		$token_data = json_decode( $body, true );

		$this->wp_bitly_logger->wpbitly_debug_log( $token_data, 'class-wp-bitly-auth.php: Processed response.' );

		$access_token = isset( $token_data['access_token'] ) ? sanitize_text_field( $token_data['access_token'] ) : null;
		$login        = isset( $token_data['login'] ) ? sanitize_text_field( $token_data['login'] ) : null;

		if ( ! $access_token ) {
			$response = array(
				'status'  => 'error',
				'message' => 'Failed to retrieve access token.',
			);
			echo wp_json_encode( $response );
			exit;
		}

		if ( ! $login ) {
			$response = array(
				'status'  => 'error',
				'message' => 'Failed to retrieve login.',
			);
			echo wp_json_encode( $response );
			exit;
		}

		$this->wp_bitly_options->set_option( 'oauth_token', $access_token );
		$this->wp_bitly_options->set_option( 'oauth_login', $login );
		$this->authorize( true );

		$response = array(
			'status'  => 'success',
			'message' => 'Got the access token.',
		);
		echo wp_json_encode( $response );
		exit;
	}
}
