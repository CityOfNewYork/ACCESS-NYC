<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Endpoints\License_Check_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Config;

class Setup_Wizard_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		$should_enqueue = Gravity_SMTP::container()->get( App_Service_Provider::SHOULD_ENQUEUE_SETUP_WIZARD );
		return is_callable( $should_enqueue ) ? $should_enqueue() : $should_enqueue;
	}

	public function data() {
		return array(
			'components' => array(
				'setup_wizard' => array(
					'endpoints' => array(
						License_Check_Endpoint::ACTION_NAME => array(
							'action' => array(
								'value'   => License_Check_Endpoint::ACTION_NAME,
								'default' => 'mock_endpoint',
							),
							'nonce'  => array(
								'value'   => wp_create_nonce( License_Check_Endpoint::ACTION_NAME ),
								'default' => 'nonce',
							),
						),
					),
				),
			),
		);
	}

}
