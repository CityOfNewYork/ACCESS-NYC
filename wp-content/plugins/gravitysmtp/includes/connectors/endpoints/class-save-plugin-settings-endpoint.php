<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_Tools\License\License_API_Connector;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;

class Save_Plugin_Settings_Endpoint extends Endpoint {

	const PARAM_SETTINGS      = 'settings';
	const PARAM_SETTING_KEY   = 'key';
	const PARAM_SETTING_VALUE = 'value';

	const PARAM_LICENSE_KEY                             = 'license_key';
	const PARAM_TEST_MODE                               = 'test_mode';
	const PARAM_EVENT_LOG_ENABLED                       = 'event_log_enabled';
	const PARAM_SAVE_EMAIL_BODY_ENABLED                 = 'save_email_body_enabled';
	const PARAM_SAVE_ATTACHMENTS_ENABLED                = 'save_attachments_enabled';
	const PARAM_EVENT_LOG_RETENTION                     = 'event_log_retention';
	const PARAM_DEBUG_LOG_ENABLED                       = 'debug_log_enabled';
	const PARAM_DEBUG_LOG_RETENTION                     = 'debug_log_retention';
	const PARAM_USAGE_ANALYTICS                         = 'usage_analytics';
	const PARAM_PER_PAGE                                = 'activity_log_per_page';
	const PARAM_MAX_EVENT_RECORDS                       = 'max_event_records';
	const PARAM_NOTIFY_WHEN_EMAIL_SENDING_FAILS_ENABLED = 'notify_when_email_sending_fails_enabled';
	const PARAM_SLACK_ALERTS_ENABLED                    = 'slack_alerts_enabled';
	const PARAM_TWILIO_ALERTS_ENABLED                   = 'twilio_alerts_enabled';

	const PARAM_SETUP_WIZARD_SHOULD_DISPLAY = 'setup_wizard_should_display';

	const ACTION_NAME = 'save_plugin_settings';

	/**
	 * @var Plugin_Opts_Data_Store;
	 */
	protected $data_store;

	/**
	 * @var License_API_Connector;
	 */
	protected $api_connector;

	protected $required_params = array();

	public function __construct( $data_store, $api_connector ) {
		$this->data_store    = $data_store;
		$this->api_connector = $api_connector;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Request must contain either an array of values to update, or a key and value to update individually.', 'gravitysmtp' ), 400 );
		}

		$settings = filter_input( INPUT_POST, self::PARAM_SETTINGS, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! empty( $settings ) ) {
			$this->handle_bulk_settings( $settings );
		} else {
			$this->handle_individual_setting();
		}
	}

	protected function handle_bulk_settings( $settings ) {
		$this->data_store->save_all( $settings );
		$data = $settings;

		if ( isset( $settings[ self::PARAM_LICENSE_KEY ] ) ) {
			$data = array_merge( $data, $this->handle_license_key( $settings[ self::PARAM_LICENSE_KEY ] ) );
		}

		wp_send_json_success( $data );
	}

	protected function handle_individual_setting() {
		$key   = htmlspecialchars( filter_input( INPUT_POST, self::PARAM_SETTING_KEY ) );
		$value = isset( $_POST[ self::PARAM_SETTING_VALUE ] ) ? $_POST[ self::PARAM_SETTING_VALUE ] : null;

		if ( is_array( $value ) ) {
			$value = filter_input( INPUT_POST, self::PARAM_SETTING_VALUE, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		} else {
			$value = htmlspecialchars( $value );
		}

		switch ( $key ) {
			case self::PARAM_LICENSE_KEY:
				$data = $this->handle_license_key_setting( $key, $value );
				break;
			default:
				$this->data_store->save( $key, $value );
				$data = array( $key => $value );
				break;
		}

		do_action( 'gravitysmtp_save_plugin_setting', $key, $value );

		wp_send_json_success( $data );
	}

	protected function handle_license_key_setting( $key, $value ) {
		$this->data_store->save( $key, $value );
		$data = array( $key => $value );

		return array_merge( $data, $this->handle_license_key( $value ) );
	}

	protected function handle_license_key( $license_key ) {
		$key_is_empty = empty( $license_key );

		if ( $key_is_empty ) {
			return array( 'license_is_valid' => null );
		}

		$license_info = $this->api_connector->check_license( $license_key );

		return array( 'license_is_valid' => License_Statuses::VALID_KEY === $license_info->get_status() );
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if (
			! isset( $_REQUEST[ self::PARAM_SETTINGS ] ) &&
			( ! isset( $_REQUEST[ self::PARAM_SETTING_KEY ] ) || ! isset( $_REQUEST[ self::PARAM_SETTING_VALUE ] ) )
		) {
			return false;
		}

		return true;
	}

}
