<?php

namespace Gravity_Forms\Gravity_Tools\API;

use Gravity_Forms\Gravity_Tools\Data\Oauth_Data_Handler;

abstract class Oauth_Handler {

	protected $supports_refresh_token = false;

	protected $response_payload_name = 'auth_payload';

	protected $payload_access_token_name = 'access_token';

	protected $payload_refresh_token_name = 'refresh_token';

	protected $namespace = '';

	/**
	 * @var Oauth_Data_Handler $data
	 */
	protected $data;

	public function __construct( $data ) {
		$this->data = $data;
	}

	public function handle_response() {
		if ( ! $this->is_response() ) {
			return;
		}

		$payload = filter_input( INPUT_POST, $this->response_payload_name );

		if ( is_string( $payload ) ) {
			$payload = json_decode( $payload, true );
		}

		if ( empty( $payload ) ) {
			return;
		}

		if ( isset( $payload[ $this->payload_access_token_name ] ) ) {
			$this->store_access_token( $payload[ $this->payload_access_token_name ] );
		}

		if ( isset( $payload[ $this->payload_refresh_token_name ] ) ) {
			$this->store_refresh_token( $payload[ $this->payload_refresh_token_name ] );
		}
	}

	public function get_access_token() {
		$token = $this->data->get( 'access_token', $this->namespace );

		if ( $this->is_valid_token( $token ) ) {
			return $token;
		}

		if ( ! $this->supports_refresh_token ) {
			return new \WP_Error( __( 'Token is invalid or expired. Please re-connect.', 'gravity-tools' ) );
		}

		return $this->refresh_expired_token();
	}

	public function get_refresh_token() {
		return $this->data->get( 'refresh_token', $this->namespace );
	}

	protected function refresh_expired_token() {
		return null;
	}

	protected function store_access_token( $token ) {
		$this->data->save( 'access_token', $token, $this->namespace );
	}

	protected function store_refresh_token( $refresh_token ) {
		$this->data->save( 'refresh_token', $refresh_token, $this->namespace );
	}

	protected function is_response() {
		return false;
	}

	abstract public function get_return_url();

	abstract public function get_oauth_url();

	abstract public function get_refresh_url();

	abstract protected function is_valid_token( $token );

	abstract public function get_connection_details();

}