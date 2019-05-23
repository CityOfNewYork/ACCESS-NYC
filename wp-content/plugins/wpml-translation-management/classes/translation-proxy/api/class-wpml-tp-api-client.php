<?php

class WPML_TP_API_Client {
	/** @var string */
	private $proxy_url;

	/** @var WP_Http $http */
	private $http;

	/** @var WPML_TP_Lock $tp_lock */
	private $tp_lock;

	/** @var WPML_TP_HTTP_Request_Filter */
	private $request_filter;

	public function __construct(
		$proxy_url,
		WP_Http $http,
		WPML_TP_Lock $tp_lock,
		WPML_TP_HTTP_Request_Filter $request_filter
	) {
		$this->proxy_url      = $proxy_url;
		$this->http           = $http;
		$this->tp_lock        = $tp_lock;
		$this->request_filter = $request_filter;
	}

	/**
	 * @param WPML_TP_API_Request $request
	 * @param bool $raw_json_response
	 *
	 * @return array|mixed|stdClass|string
	 * @throws WPML_TP_API_Exception
	 */
	public function send_request( WPML_TP_API_Request $request, $raw_json_response = false ) {
		if ( $this->tp_lock->is_locked( $request->get_url() ) ) {
			throw new WPML_TP_API_Exception( 'Communication with translation proxy is not allowed.', $request );
		}

		$response = $this->call_remote_api( $request );

		if ( ! $response || is_wp_error( $response ) || ( isset( $response['response']['code'] ) && $response['response']['code'] >= 400 ) ) {
			throw new WPML_TP_API_Exception( 'Communication error', $request, $response );
		}

		if ( isset( $response['headers']['content-type'] ) ) {
			$content_type = $response['headers']['content-type'];
			$response     = $response['body'];
			$response     = strpos( $content_type, 'zip' ) !== false ? gzdecode( $response ) : $response;

			$json_response = json_decode( $response );

			if ( $json_response ) {
				if ( $raw_json_response ) {
					$response = $json_response;
				} else {
					$response = $this->handle_json_response( $request, $json_response );
				}
			}
		}

		return $response;
	}


	/**
	 * @param WPML_TP_API_Request $request
	 *
	 * @return null|string
	 */
	private function call_remote_api( WPML_TP_API_Request $request ) {
		$context = $this->filter_request_params( $request->get_params(), $request->get_method() );

		return $this->http->request( $this->proxy_url . $request->get_url(), $context );
	}

	/**
	 * @param array  $params request parameters
	 * @param string $method HTTP request method
	 *
	 * @return array
	 */
	private function filter_request_params( $params, $method ) {
		return $this->request_filter->build_request_context( array(
			'method'    => $method,
			'body'      => $params,
			'sslverify' => true,
			'timeout'   => 60,
		) );
	}

	/**
	 * @param WPML_TP_API_Request $request
	 * @param stdClass            $response
	 *
	 * @return mixed
	 * @throws WPML_TP_API_Exception
	 */
	private function handle_json_response( WPML_TP_API_Request $request, $response ) {
		if ( $request->has_api_response() ) {
			if ( ! isset( $response->status->code ) || $response->status->code !== 0 ) {
				throw new WPML_TP_API_Exception(
					$this->generate_error_message_from_status_field( $response ),
					$request,
					$response
				);
			}
			$response = $response->response;
		}

		return $response;
	}

	private function generate_error_message_from_status_field( $response ) {
		$message = '';
		if ( isset( $response->status->message ) ) {
			if ( isset( $response->status->code ) ) {
				$message = '(' . $response->status->code . ') ';
			}
			$message .= $response->status->message;
		} else {
			$message = 'Unknown error';
		}

		return $message;
	}
}