<?php

namespace Gravity_Forms\Gravity_SMTP\Errors;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Error_Handler_Service_Provider extends Service_Provider {

	const ERROR_HANDLER = 'error_handler';

	public function register( Service_Container $container ) {
		$container->add( self::ERROR_HANDLER, function() use ( $container ) {
			$event_log_details = $container->get( Logging_Service_Provider::LOGGER );
			$events_model = $container->get( Connector_Service_Provider::EVENT_MODEL );
			$debug_logger = $container->get( Logging_Service_Provider::DEBUG_LOGGER );
			return new Error_Handler( $event_log_details, $events_model, $debug_logger );
		});
	}

	public function init( Service_Container $container ) {
		add_filter( 'pre_determine_locale', function( $data ) use ( $container ) {
			$container->get( self::ERROR_HANDLER )->handle();

			return $data;
		});
	}

}