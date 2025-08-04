<?php

namespace Gravity_Forms\Gravity_SMTP\Routing;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Routing\Handlers\Primary_Backup_Handler;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Routing_Service_Provider extends Service_Provider {

	const PRIMARY_BACKUP_HANDLER = 'primary_backup_handler';

	const HOOK_PRIORITY_PRIMARY_BACKUP = 10;

	public function register( Service_Container $container ) {
		$container->add( self::PRIMARY_BACKUP_HANDLER, function() use ( $container ) {
			return new Primary_Backup_Handler( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $container->get( Logging_Service_Provider::DEBUG_LOGGER ) );
		});
	}

	public function init( Service_Container $container ) {
		add_filter( 'gravitysmtp_connector_for_sending', function( $current_connector, $email_args ) use ( $container ) {
			if ( $current_connector ) {
				return $current_connector;
			}
			return $container->get( self::PRIMARY_BACKUP_HANDLER )->handle( $current_connector, $email_args );
		}, self::HOOK_PRIORITY_PRIMARY_BACKUP, 2 );

		add_action( 'gravitysmtp_before_email_send', function() use ( $container ) {
			$container->get( self::PRIMARY_BACKUP_HANDLER )->reset();
		}, 0 );
	}

}