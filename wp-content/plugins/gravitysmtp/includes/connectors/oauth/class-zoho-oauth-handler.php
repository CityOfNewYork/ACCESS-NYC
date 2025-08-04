<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Oauth;

use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Google;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Zoho;
use Gravity_Forms\Gravity_SMTP\Enums\Zoho_Datacenters_Enum;
use Gravity_Forms\Gravity_Tools\API\Oauth_Handler as Oauth_Handler_Base;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Zoho_Oauth_Handler extends Oauth_Handler_Base {

	protected $supports_refresh_token = true;

	protected $namespace = 'zoho';

	protected $response_payload_name = 'code';

	public function get_connection_details() {
		return array(
			'account_id' => $this->data->get( Connector_Zoho::SETTING_ACCOUNT_ID, $this->namespace )
		);
	}

	public function handle_response() {
		if ( ! $this->is_response() ) {
			return;
		}

		$url = 'https://accounts.zoho.com/oauth/v2/token';
		$state = $this->get_state();

		// Require code and state, and valid mode; otherwise redirect back.
		if ( ! isset( $_GET['code'] ) || empty( $state ) ) { //phpcs:ignore
			return;
		}

		$code = FILTER_INPUT( INPUT_GET, 'code', FILTER_DEFAULT );

		$args = array(
			'client_id'     => $this->data->get( Connector_Zoho::SETTING_CLIENT_ID, $this->namespace ),
			'client_secret' => $this->data->get( Connector_Zoho::SETTING_CLIENT_SECRET, $this->namespace ),
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => $this->get_return_url( 'settings', true ),
		);

		$url = add_query_arg( $args, $url );

		$request = wp_remote_post( $url, array( 'body' => array() ) );

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

		// Add Account ID
		$accounts_url = $this->get_api_url( 'api/accounts' );
		$headers   = array(
			'Authorization' => 'Zoho-oauthtoken ' . $response[ $this->payload_access_token_name ],
		);

		$request = wp_remote_get( $accounts_url, array( 'headers' => $headers ) );
		$response = wp_remote_retrieve_body( $request );
		$data = json_decode( $response, true );

		if ( ! isset( $data['data'][0]['accountId'] ) ) {
			return;
		}

		$this->data->save( Connector_Zoho::SETTING_ACCOUNT_ID,  $data['data'][0]['accountId'], $this->namespace );
	}

	public function get_scope() {
		return 'ZohoMail.messages.CREATE,ZohoMail.accounts.READ';
	}

	protected function refresh_expired_token() {
		$refresh_token = $this->get_refresh_token();

		if ( empty( $refresh_token ) ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$refresh_url = $this->get_refresh_url();
		$args        = array(
			'refresh_token' => $refresh_token,
			'client_id'     => $this->data->get( Connector_Zoho::SETTING_CLIENT_ID, $this->namespace ),
			'client_secret' => $this->data->get( Connector_Zoho::SETTING_CLIENT_SECRET, $this->namespace ),
			'grant_type'    => 'refresh_token',
			'redirect_uri'  => $this->get_return_url( 'settings', true ),
		);

		$url = add_query_arg( $args, $refresh_url );

		$request = wp_remote_post( $url, array( 'body' => array() ) );
		$code     = wp_remote_retrieve_response_code( $request );

		if ( (int) $code !== 200 ) {
			return new \WP_Error( __( 'Token is invalid or expired.', 'gravitysmtp' ) );
		}

		$response_body = json_decode( wp_remote_retrieve_body( $request ), true );

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

	public function get_return_url( $context = 'settings', $encode = true ) {
		$base = admin_url( 'admin.php' );

		if ( $context === 'copy' ) {
			return $base;
		}

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

		$value = add_query_arg( $args, $base );

		if ( ! $encode ) {
			return $value;
		}

		return urlencode( $value );
	}

	public function get_refresh_url() {
		return esc_url( 'https://accounts.zoho.com/oauth/v2/token' );
	}

	private function get_state( $context = 'settings' ) {
		$state = array(
			'url'   => admin_url( 'admin.php' ),
			'page'  => 'gravitysmtp-settings',
			'nonce' => wp_create_nonce( 'gravitysmtp' ),
		);

		if ( $context === 'settings' ) {
			$state['tab']         = 'integrations';
			$state['integration'] = $this->namespace;
		}

		return $state;
	}

	public function get_oauth_url( $context = 'settings' ) {
		return '';
	}

	protected function is_valid_token( $token ) {
		static $is_valid;

		if ( ! is_null( $is_valid ) ) {
			return $is_valid;
		}

		if ( empty( $token ) ) {
			return false;
		}

		$check_url = $this->get_api_url( 'api/accounts' );
		$headers   = array(
			'Authorization' => 'Zoho-oauthtoken ' . $token,
			'Content-type' => 'application/json',
			'Accept' => 'application/json',
		);

		$response = wp_remote_get( $check_url, array( 'headers' => $headers ) );
		$code     = wp_remote_retrieve_response_code( $response );

		$is_valid = (int) $code === 200;

		return $is_valid;
	}

	private function get_api_url( $endpoint ) {
		$data_center_location = $this->data->get( Connector_Zoho::SETTING_DATA_CENTER_REGION, $this->namespace );

		if ( empty( $data_center_location ) ) {
			$data_center_location = 'us';
		}

		$base = Zoho_Datacenters_Enum::url_for_datacenter( $data_center_location );

		return trailingslashit( $base ) . $endpoint;
	}

}
