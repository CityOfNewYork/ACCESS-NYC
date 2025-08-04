<?php

namespace Gravity_Forms\Gravity_SMTP\Telemetry;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Telemetry_Service_Provider extends Service_Provider {

	const TELEMETRY_BACKGROUND_PROCESSOR = 'telemetry_background_processor';
	const TELEMETRY_SNAPSHOT_DATA        = 'telemetry_snapshot_data';
	const TELEMETRY_HANDLER              = 'telemetry_handler';

	const TELEMETRY_SCHEDULED_TASK = 'gravitysmtp_telemetry_dispatcher';
	const CRON_HOOK                = 'gravitysmtp_cron';
	const BATCH_SIZE               = 10;

	public function register( Service_Container $container ) {
		$container->add( self::TELEMETRY_BACKGROUND_PROCESSOR, function () use ( $container ) {
			return new Telemetry_Background_Processor( $container->get( Utils_Service_Provider::COMMON ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ), $container->get( Utils_Service_Provider::CACHE ) );
		} );

		$container->add( self::TELEMETRY_SNAPSHOT_DATA, function () use ( $container ) {
			return new Telemetry_Snapshot_Data( $container->get( Utils_Service_Provider::COMMON ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ), $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ) );
		} );

		$container->add( self::TELEMETRY_HANDLER, function () use ( $container ) {
			return new Telemetry_Handler( $container->get( Logging_Service_Provider::DEBUG_LOGGER ), $container->get( self::TELEMETRY_SNAPSHOT_DATA ), $container->get( self::TELEMETRY_BACKGROUND_PROCESSOR ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( self::TELEMETRY_SCHEDULED_TASK, function () use ( $container ) {
			$container->get( self::TELEMETRY_HANDLER )->handle();
		} );

		add_filter( 'gravity_api_remote_post_params', function ( $post ) use ( $container ) {
			/**
			 * @var Telemetry_Snapshot_Data $snapshot
			 */
			$snapshot = $container->get( self::TELEMETRY_SNAPSHOT_DATA );
			$snapshot->record_data();
			$telemetry_data = $snapshot->get_existing_data();

			// safety check in case data doesn't exist.
			if ( empty( $telemetry_data ) ) {
				return $post;
			}

			unset( $telemetry_data['snapshot']['gravitysmtp_config'] );

			return $telemetry_data['snapshot'];
		} );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}

		add_action( self::CRON_HOOK, function() {
			do_action( self::TELEMETRY_SCHEDULED_TASK );
		} );
	}

}