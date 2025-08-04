<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Google;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Microsoft;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_Tools\API\Oauth_Handler as Oauth_Handler_Base;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Google_Oauth_Handler extends Oauth_Handler_Base {

	protected $supports_refresh_token = true;

	protected $response_payload_name = 'code';

	protected $namespace = 'google';

	public function handle_response() {
		if ( ! $this->is_response() ) {
			return;
		}

		$url = 'https://oauth2.googleapis.com/token';

		// Require code and state, and valid mode; otherwise redirect back.
		if ( ! isset( $_GET['code'] ) ) { //phpcs:ignore
			return;
		}

		$code = FILTER_INPUT( INPUT_GET, 'code', FILTER_DEFAULT );

		$body = array(
			'client_id'     => $this->data->get( Connector_Google::SETTING_CLIENT_ID, $this->namespace ),
			'client_secret' => $this->data->get( Connector_Google::SETTING_CLIENT_SECRET, $this->namespace ),
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => urldecode( $this->get_return_url( 'settings', false ) ),
		);

		$request = wp_remote_post( $url, array( 'body' => $body ) );

		if ( (int) wp_remote_retrieve_response_code( $request ) !== 200  ) { //phpcs:ignore
			return;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = json_decode( $response, true );

		if ( isset( $response[ $this->payload_access_token_name ] ) ) {
			$this->store_access_token( $response[ $this->payload_access_token_name ] );
		}

		if ( isset( $response[ $this->payload_refresh_token_name ] ) ) {
			$this->store_refresh_token( $response[ $this->payload_refresh_token_name ] );
		}
	}

	protected function refresh_expired_token() {
		$refresh_token = $this->get_refresh_token();

		if ( empty( $refresh_token ) ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$refresh_url = $this->get_refresh_url();
		$body        = array(
			'refresh_token' => $refresh_token,
			'client_id'     => $this->data->get( Connector_Google::SETTING_CLIENT_ID, $this->namespace ),
			'client_secret' => $this->data->get( Connector_Google::SETTING_CLIENT_SECRET, $this->namespace ),
			'grant_type'    => 'refresh_token',
		);

		$response = wp_remote_post( $refresh_url, array( 'body' => $body ) );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( (int) $code !== 200 ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_body['access_token'] ) ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$new_token = $response_body['access_token'];

		$this->store_access_token( $new_token );

		return $new_token;
	}

	protected function is_response() {
		$page          = filter_input( INPUT_GET, 'page' );
		$tab           = filter_input( INPUT_GET, 'tab' );
		$integration   = filter_input( INPUT_GET, 'integration' );
		$wizard_screen = filter_input( INPUT_GET, 'setup-wizard-page', FILTER_SANITIZE_NUMBER_INT );
		$payload       = filter_input( INPUT_GET, $this->response_payload_name );

		// Valid screens are a specific integration tab, or the setup wizard page.
		$is_valid_screen = $page === 'gravitysmtp-settings' &&
		                   (
			                   ( $tab === 'integrations' && $integration === $this->namespace ) ||
			                   ( ! empty( $wizard_screen ) )
		                   );

		return ! empty( $payload ) && $is_valid_screen;
	}

	public function get_return_url( $context = 'settings' ) {
		$base = admin_url( 'admin.php' );
		$args = array(
			'page' => 'gravitysmtp-settings',
		);

		if ( $context === 'settings' ) {
			$args['integration'] = $this->namespace;
			$args['tab']         = 'integrations';
		}

		if ( $context === 'wizard' ) {
			$args['tab']               = 'integrations';
			$args['setup-wizard-page'] = 4;
		}

		return urlencode( add_query_arg( $args, $base ) );
	}

	public function get_refresh_url() {
		return esc_url('https://oauth2.googleapis.com/token' );
	}

	public function get_oauth_url( $context = 'settings' ) {
		$state = array(
			'url'   => admin_url( 'admin.php' ),
			'page'  => 'gravitysmtp-settings',
			'nonce' => wp_create_nonce( 'gravitysmtp' ),
		);

		if ( $context === 'settings' ) {
			$state['tab']         = 'integrations';
			$state['integration'] = $this->namespace;
		}

		$auth_url = add_query_arg(
			array(
				'redirect_to' => $this->get_return_url( $context ),
				'state'       => base64_encode(
					json_encode(
						$state
					)
				),
				'license'     => $this->data->get( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY ),
			),
			trailingslashit( GRAVITY_API_URL ) . 'auth/gmail'
		);

		return esc_url( $auth_url );
	}

	protected function is_valid_token( $token ) {
		static $is_valid;

		if ( ! is_null( $is_valid ) ) {
			return $is_valid;
		}

		if ( empty( $token ) ) {
			return false;
		}

		$check_url = 'https://gmail.googleapis.com/gmail/v1/users/me/profile';
		$headers   = array(
			'Authorization' => 'Bearer ' . $token,
		);

		$response = wp_remote_get( $check_url, array( 'headers' => $headers ) );
		$code     = wp_remote_retrieve_response_code( $response );

		$is_valid = (int) $code === 200;

		return $is_valid;
	}

	public function get_connection_details() {
		$token     = $this->data->get( 'access_token', $this->namespace );
		$check_url = 'https://gmail.googleapis.com/gmail/v1/users/me/profile';
		$headers   = array(
			'Authorization' => 'Bearer ' . $token,
		);

		$response = wp_remote_get( $check_url, array( 'headers' => $headers ) );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( (int) $code !== 200 ) {
			return array(
				'email' => __( 'Unable to retrieve associated email.', 'gravitysmtp' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return array(
			'email' => $body['emailAddress'],
		);
	}

}
