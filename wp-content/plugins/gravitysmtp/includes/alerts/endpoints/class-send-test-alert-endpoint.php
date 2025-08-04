<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Endpoints;

use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Send_Test_Alert_Endpoint extends Endpoint {

	const PARAM_DATA          = 'data';
	const PARAM_DATA_TYPE     = 'type';
	const PARAM_DATA_SETTINGS = 'settings';

	const ACTION_NAME = 'send_test_alert';

	/**
	 * @var Alert_Connector[]
	 */
	protected $connectors;

	protected $required_params = array(
		self::PARAM_DATA,
	);

	public function __construct( $connectors ) {
		$this->connectors = $connectors;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$data = filter_input( INPUT_POST, self::PARAM_DATA, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( empty( $data[ self::PARAM_DATA_TYPE ] ) ) {
			wp_send_json_error( __( 'Data array parameter must contain a type key.', 'gravitysmtp' ), 400 );
		}

		$type = $data[ self::PARAM_DATA_TYPE ];

		if ( empty( $this->connectors[ $type ] ) ) {
			wp_send_json_error( __( 'Invalid connector type of ' . $type . ' provided.', 'gravitysmtp' ), 400 );
		}

		/**
		 * @var Alert_Connector $connector
		 */
		$connector = $this->connectors[ $type ];

		$settings = $data[ self::PARAM_DATA_SETTINGS ];

		switch ( $type ) {
			case 'slack':
			default:
				if ( empty( $settings['slack_webhook_url'] ) ) {
					wp_send_json_error( __( 'Slack connector requires a webhook_url.', 'gravitysmtp' ), 400 );
				}
				$args = array(
					'webhook_url' => $settings['slack_webhook_url'],
					'message'     => __( 'Gravity SMTP Test: Webhook is working!', 'gravitysmtp' ),
				);
				break;
			case 'twilio':
				if ( empty( $settings['twilio_account_id'] ) || empty( $settings['twilio_auth_token'] ) || empty( $settings['twilio_to_phone_number'] ) || empty( $settings['twilio_from_phone_number'] ) ) {
					wp_send_json_error( __( 'Twilio connector requires a twilio_account_id, twilio_auth_token, twilio_to_phone_number, and twilio_from_phone_number.', 'gravitysmtp' ), 400 );
				}
				$args = array(
					'account_id' => $settings['twilio_account_id'],
					'auth_token' => $settings['twilio_auth_token'],
					'to'         => $settings['twilio_to_phone_number'],
					'from'       => $settings['twilio_from_phone_number'],
					'message'    => __( 'Gravity SMTP Test: Twilio is set up!', 'gravitysmtp' ),
				);
				break;
		}

		$sent = $connector->send( $args );

		if ( ! $sent ) {
			wp_send_json_error( __( 'Could not send alert. Check Debug Log for details.', 'gravitysmtp' ), 500 );
		}

		wp_send_json_success( __( 'Alert successfully configured!', 'gravitysmtp' ) );
	}

}
