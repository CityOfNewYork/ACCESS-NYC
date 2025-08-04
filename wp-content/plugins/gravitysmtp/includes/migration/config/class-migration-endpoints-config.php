<?php

namespace Gravity_Forms\Gravity_SMTP\Migration\Config;

use Gravity_Forms\Gravity_SMTP\Apps\Migration\Endpoints\Migrate_Settings_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Migration_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Migrate_Settings_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Migrate_Settings_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Migrate_Settings_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				)
			),
		);
	}

}
