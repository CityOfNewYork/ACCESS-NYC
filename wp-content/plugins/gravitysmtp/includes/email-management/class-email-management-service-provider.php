<?php

namespace Gravity_Forms\Gravity_SMTP\Email_Management;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Email_Management\Config\Managed_Email_Types_Config;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Managed_Email_Types;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;

class Email_Management_Service_Provider extends Config_Service_Provider {

	const EMAIL_STOPPER = 'email_stopper';
	const MANAGED_EMAIL_TYPES = 'managed_email_types';

	const MANAGED_EMAIL_TYPES_CONFIG = 'managed_email_types_config';

	protected $configs = array(
		self::MANAGED_EMAIL_TYPES_CONFIG => Managed_Email_Types_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::EMAIL_STOPPER, function () use ( $container ) {
			return new Email_Stopper( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$container->add( self::MANAGED_EMAIL_TYPES, function () {
			$emails = new Managed_Email_Types();

			return $emails->types();
		} );
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		$email_types = $container->get( self::MANAGED_EMAIL_TYPES );

		/**
		 * @var Email_Stopper $stopper
		 */
		$stopper = $container->get( self::EMAIL_STOPPER );
		add_action( 'init', function () use ( $stopper, $email_types ) {
			foreach ( $email_types as $values ) {
				$type = new Managed_Email( $values['key'], $values['label'], $values['description'], $values['category'], $values['disable_callback'] );
				$stopper->add( $type );
			}
		}, 11 );

		add_action( 'init', function() use ( $container ) {
			$container->get( self::EMAIL_STOPPER )->stop_all();
		}, 12 );
	}

}