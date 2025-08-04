<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard;

use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Config\Setup_Wizard_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Config\Setup_Wizard_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Endpoints\License_Check_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Migrate_Settings_Endpoint;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;

class Setup_Wizard_Service_Provider extends Config_Service_Provider {

	const IMPORT_SETTINGS_ENDPOINT     = 'import_settings_endpoint';
	const LICENSE_CHECK_ENDPOINT       = 'license_check_endpoint';
	const SAVE_SETUP_PROGRESS_ENDPOINT = 'save_setup_progress_endpoint';

	const SETUP_WIZARD_CONFIG           = 'setup_wizard_config';
	const SETUP_WIZARD_ENDPOINTS_CONFIG = 'setup_wizard_endpoints_config';

	protected $configs = array(
		self::SETUP_WIZARD_CONFIG           => Setup_Wizard_Config::class,
		self::SETUP_WIZARD_ENDPOINTS_CONFIG => Setup_Wizard_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( self::LICENSE_CHECK_ENDPOINT, function () use ( $container ) {
			return new License_Check_Endpoint();
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . License_Check_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::LICENSE_CHECK_ENDPOINT )->handle();
		} );
	}
}
