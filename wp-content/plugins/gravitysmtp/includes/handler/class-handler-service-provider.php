<?php

namespace Gravity_Forms\Gravity_SMTP\Handler;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Handler\Config\Handler_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Handler\Endpoints\Resend_Email_Endpoint;
use Gravity_Forms\Gravity_SMTP\Handler\External\Gravity_Forms_Note_Handler;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Suppression\Suppression_Service_Provider;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Handler_Service_Provider extends Config_Service_Provider {

	const HANDLER                  = 'mail_handler';
	const HANDLER_ENDPOINTS_CONFIG = 'handler_endpoints_config';
	const NOTE_HANDLER             = 'note_handler';

	const RESEND_EMAIL_ENDPOINT = 'resend_email_endpoint';

	protected $configs = array(
		self::HANDLER_ENDPOINTS_CONFIG => Handler_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::HANDLER, function () use ( $container ) {
			$factory       = $container->get( Connector_Service_Provider::CONNECTOR_FACTORY );
			$data_store    = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$source_parser = $container->get( Utils_Service_Provider::SOURCE_PARSER );
			$suppressed_model = $container->get( Suppression_Service_Provider::SUPPRESSED_EMAILS_MODEL );

			return new Mail_Handler( $factory, $data_store, $source_parser, $suppressed_model );
		} );

		$container->add( self::RESEND_EMAIL_ENDPOINT, function () use ( $container ) {
			return new Resend_Email_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ), $container->get( Utils_Service_Provider::ATTACHMENTS_SAVER ) );
		} );

		$container->add( self::NOTE_HANDLER, function() {
			return new Gravity_Forms_Note_Handler();
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . Resend_Email_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::RESEND_EMAIL_ENDPOINT )->handle();
		} );

		if ( ! Feature_Flag_Manager::is_enabled( 'gravityforms_entry_note' ) ) {
			return;
		}

		$is_configured = Mail_Handler::is_minimally_configured();
		$test_mode = Mail_Handler::is_test_mode();

		if ( ! $is_configured && ! $test_mode ) {
			return;
		}

		/**
		 * @var Mail_Handler $handler
		 */
		$handler = $container->get( self::HANDLER );

		/**
		 * @var Event_Model $model
		 */
		$model   = $container->get( Connector_Service_Provider::EVENT_MODEL );

		/**
		 * @var Gravity_Forms_Note_Handler $note_handler
		 */
		$note_handler = $container->get( self::NOTE_HANDLER );

		add_filter( 'gform_pre_send_email', function( $email_args, $format, $notification, $entry ) use ( $handler, $note_handler ) {
			$note_handler->store_id( $entry['id'], $handler );

			return $email_args;
		}, 99, 4 );

		add_filter( 'gform_notification_note', function ( $note_args, $entry_id, $result ) use ( $handler, $model, $note_handler ) {
			if ( $result !== true ) {
				return $note_args;
			}

			$note_args['text'] = $note_handler->get_modified_entry_note( $note_args['text'], $entry_id, $handler, $model );

			return $note_args;
		}, 10, 3 );

		// When an email fails to send, clear the configuration cache for oauth providers to ensure the UI reflects any errors.
		add_action( 'gravitysmtp_on_send_failure', function( $email_id ) use ( $container ) {
			$events_model = $container->get( Connector_Service_Provider::EVENT_MODEL );
			$event = $events_model->get( $email_id );

			// Couldn't find event, bail.
			if ( empty( $event ) ) {
				return;
			}

			$service = $event['service'];

			// Not an oAuth service, bail.
			if ( ! in_array( $service, array( 'google', 'microsoft', 'zoho' ) ) ) {
				return;
			}

			$configured_key = sprintf( 'gsmtp_connector_configured_%s', $service );
			delete_transient( $configured_key );
		} );
	}

}
