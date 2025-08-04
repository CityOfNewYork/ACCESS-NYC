<?php

namespace Gravity_Forms\Gravity_SMTP\Environment;

use Gravity_Forms\Gravity_SMTP\Environment\Config\Environment_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Environment\Endpoints\Uninstall_Endpoint;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;

class Environment_Service_Provider extends Config_Service_Provider {

	const UNINSTALL_ENDPOINT = 'uninstall_endpoint';
	const ENVIRONMENT_ENDPOINTS_CONFIG = 'environment_endpoints_config';

	protected $configs = array(
		self::ENVIRONMENT_ENDPOINTS_CONFIG => Environment_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$container->add( self::UNINSTALL_ENDPOINT, function() {
			return new Uninstall_Endpoint();
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . Uninstall_Endpoint::ACTION_NAME, function() use ( $container ) {
			$container->get( self::UNINSTALL_ENDPOINT )->handle();
		} );
	}

}
