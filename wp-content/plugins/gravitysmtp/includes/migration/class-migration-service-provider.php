<?php

namespace Gravity_Forms\Gravity_SMTP\Migration;

use Gravity_Forms\Gravity_SMTP\Connectors\Config\Connector_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_SMTP\Apps\Migration\Endpoints\Migrate_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Migration\Config\Migration_Endpoints_Config;

class Migration_Service_Provider extends Config_Service_Provider {

	const MIGRATOR_COLLECTION = 'migrator_collection';
	const WP_SMTP_PRO_MIGRATOR = 'wp_smtp_pro_migrator';
	const MIGRATE_ENDPOINTS_CONFIG = 'migrate_endpoints_config';

	const MIGRATE_SETTINGS_ENDPOINT = 'migrate_settings_endpoint';

	protected $configs = array(
		self::MIGRATE_ENDPOINTS_CONFIG => Migration_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::MIGRATOR_COLLECTION, function() {
			return new Migrator_Collection();
		});

		$container->add( self::MIGRATE_SETTINGS_ENDPOINT, function () use ( $container ) {
			return new Migrate_Settings_Endpoint( $container->get( Connector_Service_Provider::CONNECTOR_FACTORY ), $container->get( Connector_Service_Provider::DATA_STORE_OPTS ), __NAMESPACE__, $container->get( Connector_Service_Provider::REGISTERED_CONNECTORS ) );
		} );
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		add_action( 'wp_ajax_' . Migrate_Settings_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::MIGRATE_SETTINGS_ENDPOINT )->handle();
		} );
	}

}