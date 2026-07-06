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
 * Bitly API wrapper functions.
 *
 * @since      2.6.0
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */
class Wp_Bitly_Api {

	/**
	 * Initialize
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
	}

	/**
	 * Retrieve the requested API endpoint.
	 *
	 * @since 2.0
	 * @param   string $api_call Which endpoint do we need.
	 * @return  string Returns the URL for our requested API endpoint.
	 */
	public function wpbitly_api( $api_call ) {

		$api_links = array(
			'shorten'       => '/v4/shorten',
			'expand'        => '/v4/expand',
			'link/clicks'   => '/v4/bitlinks/%1$s/clicks',
			'user'          => '/v4/user',
			'bsds'          => '/v4/bsds',
			'groups'        => '/v4/groups',
			'organizations' => '/v4/organizations',
		);

		if ( ! array_key_exists( $api_call, $api_links ) ) {
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'WP Bitly Error: No such API endpoint.', 'wp-bitly' ), '2.6.0' );
			return '';
		}

		return WPBITLY_BITLY_API . $api_links[ $api_call ];
	}

	/**
	 * WP Bitly wrapper for wp_remote_get that verifies a successful response.
	 *
	 * @since   2.1
	 * @param   string $url The API endpoint we're contacting.
	 * @param   string $token The API token.
	 * @return  bool|array False on failure, array on success.
	 */
	public function wpbitly_get( $url, $token ) {

		$http_response = wp_remote_get(
			$url,
			array(
				'timeout'   => '30',
				'headers'   => array( 'Authorization' => "Bearer $token" ),
				'sslverify' => WBBITLY_SSL_VERIFY,
			)
		);
		if ( is_wp_error( $http_response ) ) {
			error_log( 'HTTP request failed: ' . print_r( $http_response, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return false;
		}

		$body = wp_remote_retrieve_body( $http_response );
		if ( 200 !== wp_remote_retrieve_response_code( $http_response ) ) {
			error_log( 'Error response: ' . print_r( $body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return false;
		}
		return json_decode( $body, true );
	}

	/**
	 * WP Bitly wrapper for wp_remote_post that verifies a successful response.
	 *
	 * @since   2.1
	 * @param   string $url The API endpoint we're contacting.
	 * @param   string $token The API token.
	 * @param   array  $params The params sent to the API endpoint.
	 * @return  bool|array False on failure, array on success.
	 */
	public function wpbitly_post( $url, $token, $params = array() ) {

		$http_response = wp_remote_post(
			$url,
			array(
				'timeout'   => '30',
				'headers'   => array(
					'Authorization' => "Bearer $token",
					'Content-Type'  => 'application/json',
				),
				'method'    => 'POST',
				'body'      => wp_json_encode( $params ),
				'sslverify' => WBBITLY_SSL_VERIFY,
			)
		);
		// Check for an HTTP error.
		if ( is_wp_error( $http_response ) ) {
			error_log( 'HTTP request failed: ' . print_r( $http_response, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return false;
		}
		$body = wp_remote_retrieve_body( $http_response );
		if ( 200 !== wp_remote_retrieve_response_code( $http_response ) ) {
			error_log( 'Error response: ' . print_r( $body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			return false;
		}
		return json_decode( $body, true );
	}
}
