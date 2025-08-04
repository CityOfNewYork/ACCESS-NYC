<?php

namespace Gravity_Forms\Gravity_SMTP\Alerts\Config;

use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Save_Alerts_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Send_Test_Alert_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Alerts_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Save_Alerts_Settings_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Save_Alerts_Settings_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Save_Alerts_Settings_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Send_Test_Alert_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Send_Test_Alert_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Send_Test_Alert_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				),
			),
		);
	}

}