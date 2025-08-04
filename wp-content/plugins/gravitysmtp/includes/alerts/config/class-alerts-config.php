<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Config;

use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Save_Alerts_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Apps\Endpoints\Get_Dashboard_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;

class Alerts_Config extends Config {

	const SETTING_SEND_ON_FAIL         = 'send_on_fail';
	const SETTING_ENABLE_SLACK_ALERTS  = 'enable_slack_alerts';
	const SETTING_ENABLE_TWILIO_ALERTS = 'enable_twilio_alerts';

	const SETTING_SLACK_ALERTS      = 'slack_alerts';
	const SETTING_SLACK_WEBHOOK_URL = 'slack_webhook_url';

	const SETTING_TWILIO_ALERTS            = 'twilio_alerts';
	const SETTING_TWILIO_ACCOUNT_ID        = 'twilio_account_id';
	const SETTING_TWILIO_ACCOUNT_NAME      = 'twilio_account_name';
	const SETTING_TWILIO_AUTH_TOKEN        = 'twilio_auth_token';
	const SETTING_TWILIO_FROM_PHONE_NUMBER = 'twilio_from_phone_number';
	const SETTING_TWILIO_TO_PHONE_NUMBER   = 'twilio_to_phone_number';

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	public function should_enqueue() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		if ( $page !== 'gravitysmtp-settings' ) {
			return false;
		}

		return true;
	}

	public function data() {
		return array(
			'components' => array(
				'settings' => array(
					'i18n' => $this->i18n_values(),
					'data' => $this->data_values(),
				),
			)
		);
	}

	protected function i18n_values() {
		return array(
			'alerts' => array(
				'top_heading'                        => esc_html__( 'Alerts', 'gravitysmtp' ),
				'top_content'                        => __( "Use these settings to configure alerts for failed email sending attempts. Set up notifications through Webhooks or SMS (via Twilio) to ensure you're informed when an email fails to send. Multiple integrations can be added and managed.", 'gravitysmtp' ),
				'alerts_box_heading'                 => esc_html__( 'Alerts Settings', 'gravitysmtp' ),
				'notify_heading'                     => esc_html__( 'When to Notify', 'gravitysmtp' ),
				'email_request_fails_label'          => esc_html__( 'Email send request fails', 'gravitysmtp' ),
				'email_request_fails_help_text'      => esc_html__( 'Enable this option send an alert when an email send attempt fails for any reason.', 'gravitysmtp' ),
				'alert_threshold_count_label'        => esc_html__( 'Failure Amount', 'gravitysmtp' ),
				'alert_threshold_count_help_text'    => esc_html__( 'The number of failures to trigger an alert.', 'gravitysmtp' ),
				'alert_threshold_interval_label'     => esc_html__( 'Alert Rate', 'gravitysmtp' ),
				'alert_threshold_interval_help_text' => esc_html__( 'Interval for sending alerts about failures (in minutes).', 'gravitysmtp' ),
				'slack_heading'                      => esc_html__( 'Webhooks', 'gravitysmtp' ),
				'slack_alerts_label'                 => esc_html__( 'Webhook Alerts', 'gravitysmtp' ),
				'slack_alerts_help_text'             => esc_html__( "Get notified via webhook or SMS (Twilio) when emails fail to send. Webhooks can be used with services like Slack, Zapier, and more.", 'gravitysmtp' ),
				'slack_webhook_add_button_label'     => esc_html__( 'Add Webhook', 'gravitysmtp' ),
				'slack_webhook_delete_button_label'  => esc_html__( 'Delete', 'gravitysmtp' ),
				'start_sending_test_alert'           => esc_html__( 'Sending a test alert from the alerts settings page.', 'gravitysmtp' ),
				'snackbar_test_alert_success'        => esc_html__( 'Alert successfully configured!', 'gravitysmtp' ),
				'snackbar_test_alert_error'          => esc_html__( 'Could not send alert. Check Debug Log for details.', 'gravitysmtp' ),
				'twilio_heading'                     => esc_html__( 'Twilio', 'gravitysmtp' ),
				'twilio_alerts_block_heading'        => esc_html__( 'Twilio Account: %s', 'gravitysmtp' ),
				'twilio_alerts_label'                => esc_html__( 'SMS via Twilio Alerts', 'gravitysmtp' ),
				'twilio_alerts_help_text'            => __( "Enter the Twilio account you'd like to use to send alerts when email sending fails.", 'gravitysmtp' ),
				'twilio_account_add_button_label'    => esc_html__( 'Add Another Account', 'gravitysmtp' ),
				'twilio_account_delete_button_label' => esc_html__( 'Delete', 'gravitysmtp' ),
				'save_settings_button_label'         => esc_html__( 'Save Settings', 'gravitysmtp' ),
				'drag_button_label'                  => esc_html__( 'Click to toggle drag and drop.', 'gravitysmtp' ),
				'begin_drag_notice'                  => __( 'Entering drag and drop for item %1$s.', 'gravitysmtp' ),
				'end_drag_notice'                    => __( 'Exiting drag and drop for item %1$s.', 'gravitysmtp' ),
				'end_drop_notice'                    => __( 'Item %1$s moved to position %2$s.', 'gravitysmtp' ),
				'move_item_notice'                   => __( 'Moving item %1$s to position %2$s.', 'gravitysmtp' ),
			)
		);
	}

	private function empty_twilio_item() {
		return array(
			'repeater_item_block_content_title' => esc_html__( 'Twilio Account:', 'gravitysmtp' ),
			'repeater_item_collapsed'           => false,
			'repeater_item_id'                  => 'repeater-twilio-alerts-0',
			'twilio_account_name'               => '',
			'twilio_account_id'                 => '',
			'twilio_auth_token'                 => '',
			'twilio_from_phone_number'          => '',
			'twilio_to_phone_number'            => '',
		);
	}

	private function empty_slack_item() {
		return array(
			'repeater_item_id'  => 'repeater-slack-alerts-0',
			'slack_webhook_url' => '',
		);
	}

	private function default_setting_values() {
		return array(
			Save_Alerts_Settings_Endpoint::PARAM_NOTIFY_WHEN_EMAIL_FAILS => false,
			Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS_ENABLED    => false,
			Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS            => array(),
			Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS_ENABLED   => false,
			Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS           => array(),
			Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT    => 5,
			Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL => 5,
		);
	}

	protected function data_values() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		// todo: not sure these defaults work correctly
		$alerts_settings   = $plugin_data_store->get_plugin_setting( Save_Alerts_Settings_Endpoint::PARAM_SETTINGS, $this->default_setting_values() );

		$notify_when_email_sending_fails_enabled = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_NOTIFY_WHEN_EMAIL_FAILS ] );
		$slack_alerts_enabled                    = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS_ENABLED ] );
		$twilio_alerts_enabled                   = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS_ENABLED ] );
		$alert_threshold_count				     = isset( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT ] ) ? $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT ] : 5;
		$alert_threshold_interval				 = isset( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL ] ) ? $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL ] : 5;

		$slack_alerts  = ! empty( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS ] ) ? $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS ] : array( $this->empty_slack_item() );
		$twilio_alerts = ! empty( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS ] ) ? $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS ] : array( $this->empty_twilio_item() );

		// Fix boolean strings
		foreach ( $twilio_alerts as $key => $values ) {
			$values['repeater_item_collapsed'] = Booliesh::get( $values['repeater_item_collapsed'] );
			$twilio_alerts[ $key ]             = $values;
		}

		return array(
			'alerts_settings' => array(
				'notify_when_email_sending_fails_enabled' => $notify_when_email_sending_fails_enabled,
				'slack_alerts_enabled'                    => $slack_alerts_enabled,
				'twilio_alerts_enabled'                   => $twilio_alerts_enabled,
				'alert_threshold_count'                   => $alert_threshold_count,
				'alert_threshold_interval'                => $alert_threshold_interval,
				self::SETTING_SLACK_ALERTS                => array(
					'fields' => array(
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'     => esc_html__( 'Webhook URL', 'gravitysmtp' ),
									'isVisible' => false,
								),
								'name'            => self::SETTING_SLACK_WEBHOOK_URL,
							),
						),
						array(
							'component' => 'Button',
							'props'     => array(
								'label'        => esc_html__( 'Test Webhook', 'gravitysmtp' ),
								'icon'         => 'play',
								'iconPosition' => 'leading',
								'iconPrefix'   => 'gravitysmtp-admin-icon',
								'size'         => 'size-height-m',
								'type'         => 'white',
							),
						)
					),
					'items'  => $slack_alerts,
					'path'   => 'gravitysmtp_admin_config.components.settings.data.alerts_settings.' . self::SETTING_SLACK_ALERTS . '.items',
				),
				self::SETTING_TWILIO_ALERTS               => array(
					'fields' => array(
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'  => esc_html__( 'Account Name', 'gravitysmtp' ),
									'size'   => 'text-sm',
									'weight' => 'medium',
								),
								'name'            => self::SETTING_TWILIO_ACCOUNT_NAME,
							),
						),
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'  => esc_html__( 'Twilio Account SID', 'gravitysmtp' ),
									'size'   => 'text-sm',
									'weight' => 'medium',
								),
								'name'            => self::SETTING_TWILIO_ACCOUNT_ID,
							),
						),
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'  => esc_html__( 'Twilio Auth Token', 'gravitysmtp' ),
									'size'   => 'text-sm',
									'weight' => 'medium',
								),
								'name'            => self::SETTING_TWILIO_AUTH_TOKEN,
							),
						),
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'  => esc_html__( 'From Phone Number', 'gravitysmtp' ),
									'size'   => 'text-sm',
									'weight' => 'medium',
								),
								'name'            => self::SETTING_TWILIO_FROM_PHONE_NUMBER,
							),
						),
						array(
							'component' => 'Input',
							'props'     => array(
								'labelAttributes' => array(
									'label'  => esc_html__( 'To Phone Number', 'gravitysmtp' ),
									'size'   => 'text-sm',
									'weight' => 'medium',
								),
								'name'            => self::SETTING_TWILIO_TO_PHONE_NUMBER,
							),
						),
						array(
							'component' => 'Button',
							'props'     => array(
								'customClasses' => [ 'gravitysmtp-alerts-settings__twilio-test-connection' ],
								'label'        => esc_html__( 'Test Connection', 'gravitysmtp' ),
								'icon'         => 'play',
								'iconPosition' => 'leading',
								'iconPrefix'   => 'gravitysmtp-admin-icon',
								'size'         => 'size-height-m',
								'type'         => 'white',
							),
						),
					),
					'items'  => $twilio_alerts,
					'path'   => 'gravitysmtp_admin_config.components.settings.data.alerts_settings.' . self::SETTING_TWILIO_ALERTS . '.items',
				),
			),
		);
	}
}
