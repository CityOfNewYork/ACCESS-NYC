<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts;

use Gravity_Forms\Gravity_SMTP\Alerts\Config\Alerts_Config;
use Gravity_Forms\Gravity_SMTP\Alerts\Config\Alerts_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Slack_Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Alerts\Connectors\Twilio_Alert_Connector;
use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Save_Alerts_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Send_Test_Alert_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Alerts_Service_Provider extends Config_Service_Provider {

	const FAILED_EMAILS_ALERT_ACTION_NAME = 'failed_emails_alert_action';

	const ALERTS_CONFIG           = 'alerts_config';
	const ALERTS_ENDPOINTS_CONFIG = 'alerts_endpoints_config';

	const TWILIO_ALERT_CONNECTOR        = 'twilio_alert_connector';
	const SLACK_ALERT_CONNECTOR         = 'slack_alert_connector';
	const SAVE_ALERTS_SETTINGS_ENDPOINT = 'save_alerts_settings_endpoint';
	const ALERTS_HANDLER                = 'alerts_handler';
	const SEND_TEST_ALERT_ENDPOINT      = 'send_test_alert_endpoint';

	protected $configs = array(
		self::ALERTS_CONFIG           => Alerts_Config::class,
		self::ALERTS_ENDPOINTS_CONFIG => Alerts_Endpoints_Config::class
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::TWILIO_ALERT_CONNECTOR, function () use ( $container ) {
			$debug_logger = $container->get( Logging_Service_Provider::DEBUG_LOGGER );

			return new Twilio_Alert_Connector( $debug_logger );
		} );

		$container->add( self::SLACK_ALERT_CONNECTOR, function () use ( $container ) {
			$debug_logger = $container->get( Logging_Service_Provider::DEBUG_LOGGER );

			return new Slack_Alert_Connector( $debug_logger );
		} );

		$container->add( self::SAVE_ALERTS_SETTINGS_ENDPOINT, function () use ( $container ) {
			$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS );

			return new Save_Alerts_Settings_Endpoint( $plugin_data_store );
		} );

		$container->add( self::SEND_TEST_ALERT_ENDPOINT, function () use ( $container ) {
			$connectors = array(
				'slack'  => $container->get( self::SLACK_ALERT_CONNECTOR ),
				'twilio' => $container->get( self::TWILIO_ALERT_CONNECTOR ),
			);

			return new Send_Test_Alert_Endpoint( $connectors );
		} );

		$container->add( self::ALERTS_HANDLER, function () use ( $container ) {
			$connectors = array(
				'slack'  => $container->get( self::SLACK_ALERT_CONNECTOR ),
				'twilio' => $container->get( self::TWILIO_ALERT_CONNECTOR ),
			);

			$data_store  = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$event_model = $container->get( Connector_Service_Provider::EVENT_MODEL );

			return new Alerts_Handler( $event_model, $data_store, $connectors );
		} );
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		add_action( 'wp_ajax_' . Save_Alerts_Settings_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SAVE_ALERTS_SETTINGS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Send_Test_Alert_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SEND_TEST_ALERT_ENDPOINT )->handle();
		} );

		if ( ! wp_next_scheduled( self::FAILED_EMAILS_ALERT_ACTION_NAME ) ) {
			wp_schedule_event( time(), 'every-minute', self::FAILED_EMAILS_ALERT_ACTION_NAME );
		}

		add_action( 'gravitysmtp_on_send_failure', function ( $email_id ) use ( $container ) {
			$container->get( self::ALERTS_HANDLER )->record_failed_email();
		} );

		add_action( self::FAILED_EMAILS_ALERT_ACTION_NAME, function () use ( $container ) {
			$container->get( self::ALERTS_HANDLER )->failed_emails_alert();
		} );
	}

}