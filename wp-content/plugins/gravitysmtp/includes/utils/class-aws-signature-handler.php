<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class AWS_Signature_Handler {

	const ISO8601_BASIC = 'Ymd\THis\Z';

	/**
	 * @var string AWS Access ID
	 */
	protected $id;

	/**
	 * @var string AWS Access Secret
	 */
	protected $secret;

	/**
	 * @var AWS Region (defaults to us-east-1)
	 */
	protected $region;

	/**
	 * Get properly-signed request data for sending an SES message.
	 *
	 * @since 1.4.0
	 *
	 * @param $body
	 * @param $id
	 * @param $secret
	 * @param $region
	 *
	 * @return array
	 */
	public function get_request_data( $body, $id, $secret, $region ) {
		$this->id     = $id;
		$this->secret = $secret;
		$this->region = $region;

		$query_form = $this->build_query_from_body( $body );

		$longform_date  = gmdate( self::ISO8601_BASIC );
		$shortform_date = substr( $longform_date, 0, 8 );
		$headers        = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Host'         => $this->get_host(),
			'X-Amz-Date'   => $longform_date,
		);

		$signature = $this->generate_auth_signature( $headers, $query_form, $longform_date, $shortform_date );

		$headers['Authorization'] = $this->get_auth_header( $headers, $signature, $shortform_date );

		$url = sprintf( 'https://%s/', $this->get_host() );

		return array(
			'body'    => $query_form,
			'headers' => $headers,
			'url'     => $url,
		);
	}

	/**
	 * Build a query string from the body array we provide.
	 *
	 * @param $body
	 *
	 * @return string
	 */
	private function build_query_from_body( $body ) {
		$query = array();

		foreach ( $body as $key => $value ) {
			$query = $this->recursively_build_key( $query, $key, $value );
		}

		return http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * AWS requires a very-specific structure for URL-encoded values; match that structure here recursively.
	 *
	 * @since 1.4.0
	 *
	 * @param $query
	 * @param $key
	 * @param $data
	 *
	 * @return mixed
	 */
	private function recursively_build_key( $query, $key, $data ) {
		if ( ! is_array( $data ) ) {
			$query[ $key ] = $data;

			return $query;
		}

		foreach ( $data as $sub_key => $sub_value ) {
			$new_key = sprintf( '%s.%s', $key, $sub_key );
			$query   = $this->recursively_build_key( $query, $new_key, $sub_value );
		}

		return $query;
	}

	/**
	 * Generate a valid auth signature.
	 *
	 * @since 1.4.0
	 *
	 * @param $headers
	 * @param $body
	 * @param $longform_date
	 * @param $shortform_date
	 *
	 * @return string
	 */
	protected function generate_auth_signature( $headers, $body = '', $longform_date = '', $shortform_date = '') {
		$id          = $this->id;
		$secret      = $this->secret;
		$signing_key = $this->get_signing_key( $shortform_date );

		$canonical_request = $this->create_canonical_request( $body, $headers );
		$string_to_sign    = $this->create_string_to_sign( $canonical_request, $longform_date, $shortform_date );
		$signature         = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		return $signature;
	}

	/**
	 * Get the formatted Auth Header.
	 *
	 * @since 1.4.0
	 *
	 * @param $headers
	 * @param $signature
	 * @param $shortform_date
	 *
	 * @return string
	 */
	protected function get_auth_header( $headers, $signature, $shortform_date ) {
		$id             = $this->id;
		$region         = $this->region;
		$algo           = 'AWS4-HMAC-SHA256';
		$signed_headers = $this->get_signed_headers( $headers );
		$credential     = sprintf( '%s/%s/%s/%s/aws4_request', $id, $shortform_date, $region, 'ses' );

		return sprintf( '%s Credential=%s, SignedHeaders=%s, Signature=%s', $algo, $credential, $signed_headers, $signature );
	}

	/**
	 * Get the proper host for this request.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	private function get_host() {
		return sprintf( 'email.%s.amazonaws.com', $this->region );
	}

	/**
	 * Create a canonical request.
	 *
	 * @since 1.4.0
	 *
	 * @param $payload
	 * @param $headers
	 *
	 * @return string
	 */
	private function create_canonical_request( $payload, $headers ) {
		$method                 = 'POST';
		$canonical_uri          = '/';
		$canonical_query_string = '';
		$canonical_headers      = $this->get_canonical_headers( $headers );
		$signed_headers         = $this->get_signed_headers( $headers );
		$hashed_payload         = $this->get_hashed_payload( $payload );

		return sprintf( "%s\n%s\n%s\n%s\n%s\n%s", $method, $canonical_uri, $canonical_query_string, $canonical_headers, $signed_headers, $hashed_payload );
	}

	/**
	 * Hash the url-encoded payload.
	 *
	 * @since 1.4.0
	 *
	 * @param $payload
	 *
	 * @return string
	 */
	private function get_hashed_payload( $payload ) {
		return hash( 'sha256', $payload );
	}

	/**
	 * Get canonical headers.
	 *
	 * @since 1.4.0
	 *
	 * @param $headers
	 *
	 * @return string
	 */
	private function get_canonical_headers( $headers ) {
		$headers_string = '';
		foreach ( $headers as $key => $value ) {
			$headers_string .= sprintf( "%s:%s\n", strtolower( $key ), trim( $value ) );
		}

		return $headers_string;
	}

	/**
	 * Get signed headers.
	 *
	 * @since 1.4.0
	 *
	 * @param $headers
	 *
	 * @return string
	 */
	private function get_signed_headers( $headers ) {
		$keys      = array_keys( $headers );
		$formatted = array();

		foreach ( $keys as $key ) {
			$formatted[] = strtolower( $key );
		}

		return join( ';', $formatted );
	}

	/**
	 * Create the string to sign for the auth header.
	 *
	 * @since 1.4.0
	 *
	 * @param $canonical_request
	 * @param $longform_date
	 * @param $shortform_date
	 *
	 * @return string
	 */
	private function create_string_to_sign( $canonical_request, $longform_date, $shortform_date ) {
		$algo           = 'AWS4-HMAC-SHA256';
		$timestamp      = $longform_date;
		$scope          = $this->generate_scope( $shortform_date );
		$hashed_request = hash( 'sha256', $canonical_request );

		return sprintf( "%s\n%s\n%s\n%s", $algo, $timestamp, $scope, $hashed_request );
	}

	/**
	 * Generate a scope string.
	 *
	 * @since 1.4.0
	 *
	 * @param $shortform_date
	 *
	 * @return string
	 */
	private function generate_scope( $shortform_date ) {
		$region = $this->region;

		return sprintf( '%s/%s/%s/aws4_request', $shortform_date, $region, 'ses' );
	}

	/**
	 * Get the signing key from our secret key.
	 *
	 * @since 1.4.0
	 *
	 * @param $shortform_date
	 *
	 * @return string
	 */
	private function get_signing_key( $shortform_date ) {
		$secret = $this->secret;
		$region = $this->region;

		$date_key    = hash_hmac( 'sha256', $shortform_date, "AWS4{$secret}", true );
		$region_key  = hash_hmac( 'sha256', $region, $date_key, true );
		$service_key = hash_hmac( 'sha256', 'ses', $region_key, true );

		return hash_hmac( 'sha256', 'aws4_request', $service_key, true );
	}
}