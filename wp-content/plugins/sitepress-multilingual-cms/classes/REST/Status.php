<?php

namespace WPML\Core\REST;

class Status {

	const PING_KEY                = 'wp-rest-enabled-ping';
	const CACHE_EXPIRATION_IN_SEC = 3600;

	const ENABLED  = 'enabled';
	const DISABLED = 'disabled';
	const TIMEOUT  = 'timeout';

	/** @var \WP_Http */
	private $wp_http;

	/**
	 * @param \WP_Http $wp_http
	 */
	public function __construct( \WP_Http $wp_http ) {
		$this->wp_http = $wp_http;
	}


	public function isEnabled() {
		// Check this condition first to avoid infinite loop in testing PING request made below.
		if ( wpml_is_rest_request() ) {
			return true;
		}

		$filters = [ 'json_enabled', 'json_jsonp_enabled', 'rest_jsonp_enabled', 'rest_enabled' ];
		foreach ( $filters as $filter ) {
			if ( ! apply_filters( $filter, true ) ) {
				return false;
			}
		}

		return $this->is_rest_accessible();
	}

	/**
	 * @return bool
	 */
	private function is_rest_accessible() {
		$value = $this->cacheInTransient(
			function () {
				return $this->pingRestEndpoint();
			}
		);

		return self::DISABLED !== $value;
	}

	/**
	 * @param callable $callback
	 *
	 * @return mixed
	 */
	private function cacheInTransient( callable $callback ) {
		$value = get_transient( self::PING_KEY );
		if ( ! $value ) {
			$value = $callback();
			set_transient( self::PING_KEY, $value, self::CACHE_EXPIRATION_IN_SEC );
		}

		return $value;
	}

	/**
	 * @return string
	 */
	private function pingRestEndpoint() {
		$url = get_rest_url();

		$response = $this->wp_http->get(
			$url,
			[
				'timeout' => 5,
				'headers' => [
					'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
				],
				'cookies' => $this->getCookiesWithoutSessionId(),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $this->isTimeout( $response ) ? self::TIMEOUT : self::DISABLED;
		}

		return isset( $response['response']['code'] ) && $response['response']['code'] === 200 ? self::ENABLED : self::DISABLED;
	}

	/**
	 * The PHP session ID causes the request to be blocked if some theme/plugin
	 * calls `session_start` (this always leads to hit the timeout).
	 *
	 * @return array
	 */
	private function getCookiesWithoutSessionId() {
		return array_diff_key( $_COOKIE, [ 'PHPSESSID' => '' ] );
	}

	/**
	 * @param \WP_Error $response
	 *
	 * @return bool
	 */
	private function isTimeout( \WP_Error $response ) {
		return strpos( $response->get_error_message(), 'cURL error 28: Operation timed out after' ) === 0;
	}

}
