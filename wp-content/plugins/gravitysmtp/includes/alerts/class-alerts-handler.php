<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts;

use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Slack_Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Twilio_Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Save_Alerts_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Alerts_Handler {

	const ALERTS_DATA_TRANSIENT = 'gravitysmtp_alerts_data';

	/**
	 * @var Event_Model
	 */
	protected $events;

	/**
	 * @var Data_Store_Router
	 */
	protected $plugin_data;

	/**
	 * @var Alert_Connector[]
	 */
	protected $connectors;

	public function __construct( $events, $plugin_data, $connectors ) {
		$this->events      = $events;
		$this->plugin_data = $plugin_data;
		$this->connectors  = $connectors;
	}

	protected function default_settings() {
		return array(
			Save_Alerts_Settings_Endpoint::PARAM_NOTIFY_WHEN_EMAIL_FAILS  => false,
			Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS_ENABLED     => false,
			Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS             => array(),
			Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS_ENABLED    => false,
			Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS            => array(),
			Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT    => 5,
			Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL => 5,
		);
	}

	public function record_failed_email() {
		$current_data         = get_transient( self::ALERTS_DATA_TRANSIENT );
		$alert_count_interval = $this->plugin_data->get_plugin_setting( Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL, 5 );

		if ( $current_data !== false ) {
			$current_data['count'] += 1;
			$created_at            = new \DateTime( date( 'Y-m-d H:i:s', $current_data['created_at'] ) );
			$now                   = new \DateTime();
			$interval              = $now->diff( $created_at );
			$seconds_diff          = (int) $interval->format( '%i' ) * 60 + ( (int) $interval->format( '%s' ) );
			$new_exp               = ( MINUTE_IN_SECONDS * ( $alert_count_interval + 2 ) ) - $seconds_diff;

			set_transient( self::ALERTS_DATA_TRANSIENT, $current_data, $new_exp );

			return;
		}

		$current_data = array(
			'count'      => 1,
			'created_at' => time(),
		);

		set_transient( self::ALERTS_DATA_TRANSIENT, $current_data, MINUTE_IN_SECONDS * ( $alert_count_interval + 2 ) );
	}

	public function failed_emails_alert() {
		$alerts_settings = $this->plugin_data->get_plugin_setting( Save_Alerts_Settings_Endpoint::PARAM_SETTINGS, $this->default_settings() );
		$logging_enabled = $this->plugin_data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, false );

		$send_on_fail_enabled = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_NOTIFY_WHEN_EMAIL_FAILS ] );

		// This alert trigger is not enabled.
		if ( ! $send_on_fail_enabled ) {
			return;
		}

		$count    = isset( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT ] ) ? $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT ] : 5;
		$failures = get_transient( self::ALERTS_DATA_TRANSIENT );

		if ( false === $failures ) {
			return;
		}

		if ( $failures['count'] < $count ) {
			return;
		}

		$slack_enabled = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS_ENABLED ] );

		if ( $slack_enabled ) {
			$this->send_slack_alerts( $failures['count'], $logging_enabled, $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS ] );
		}

		$twilio_enabled = Booliesh::get( $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS_ENABLED ] );

		if ( $twilio_enabled ) {
			$this->send_twilio_alerts( $failures['count'], $logging_enabled, $alerts_settings[ Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS ] );
		}

		delete_transient( self::ALERTS_DATA_TRANSIENT );
	}

	private function failed_email_message( $count, $logging_enabled ) {
		$base_url = admin_url( 'admin.php' );

		if ( $logging_enabled ) {
			$url = add_query_arg( array(
				'page'     => 'gravitysmtp-activity-log',
			), $base_url );
		} else {
			$url = add_query_arg( array(
				'page' => 'gravitysmtp-dashboard',
			), $base_url );
		}

		return esc_html__( sprintf( 'Your site %s has experienced %d email send failures. For more details visit %s', get_bloginfo( 'name' ), $count, $url ) );
	}

	private function send_slack_alerts( $count, $logging_enabled, $alerts ) {
		if ( empty( $alerts ) ) {
			return;
		}

		/**
		 * @var Slack_Alert_Connector $slack_connector
		 */
		$slack_connector = $this->connectors['slack'];
		$message         = $this->failed_email_message( $count, $logging_enabled );

		foreach ( $alerts as $alert ) {
			$args = array(
				'webhook_url' => $alert['slack_webhook_url'],
				'message'     => $message
			);

			$slack_connector->send( $args );
		}
	}

	private function send_twilio_alerts( $count, $logging_enabled, $alerts ) {
		if ( empty( $alerts ) ) {
			return;
		}

		/**
		 * @var Twilio_Alert_Connector $twilio_connector
		 */
		$twilio_connector = $this->connectors['twilio'];
		$message          = $this->failed_email_message( $count, $logging_enabled );

		foreach ( $alerts as $alert ) {
			$args = array(
				'account_id' => $alert['twilio_account_id'],
				'auth_token' => $alert['twilio_auth_token'],
				'to'         => $alert['twilio_to_phone_number'],
				'from'       => $alert['twilio_from_phone_number'],
				'message'    => $message,
			);

			$twilio_connector->send( $args );
		}
	}

}
