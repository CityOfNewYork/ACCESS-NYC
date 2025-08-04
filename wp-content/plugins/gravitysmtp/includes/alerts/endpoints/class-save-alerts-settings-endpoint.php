<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Endpoints;

use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Save_Alerts_Settings_Endpoint extends Endpoint {

	const PARAM_SETTINGS = 'alerts_settings';

	const PARAM_ALERT_THRESHOLD_COUNT    = 'alert_threshold_count';
	const PARAM_ALERT_THRESHOLD_INTERVAL = 'alert_threshold_interval';

	const PARAM_NOTIFY_WHEN_EMAIL_FAILS = 'notify_when_email_sending_fails_enabled';

	const PARAM_SLACK_ALERTS_ENABLED = 'slack_alerts_enabled';
	const PARAM_SLACK_ALERTS         = 'slack_alerts';

	const PARAM_TWILIO_ALERTS_ENABLED = 'twilio_alerts_enabled';
	const PARAM_TWILIO_ALERTS         = 'twilio_alerts';

	const ACTION_NAME = 'save_alerts_settings';

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $plugin_data_store;

	protected $required_params = array(
		self::PARAM_SETTINGS,
	);

	public function __construct( $plugin_data_store ) {
		$this->plugin_data_store = $plugin_data_store;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$settings = filter_input( INPUT_POST, self::PARAM_SETTINGS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$this->plugin_data_store->save( self::PARAM_SETTINGS, $settings );

		wp_send_json_success( $settings );
	}

}