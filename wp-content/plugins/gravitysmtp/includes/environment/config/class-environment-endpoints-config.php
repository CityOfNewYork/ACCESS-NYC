<?php

namespace Gravity_Forms\Gravity_SMTP\Environment\Config;

use Gravity_Forms\Gravity_SMTP\Environment\Endpoints\Uninstall_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Environment_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Uninstall_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Uninstall_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Uninstall_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				),
			),
		);
	}

}
