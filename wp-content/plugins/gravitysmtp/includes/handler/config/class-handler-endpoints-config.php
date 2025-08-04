<?php

namespace Gravity_Forms\Gravity_SMTP\Handler\Config;

use Gravity_Forms\Gravity_SMTP\Handler\Endpoints\Resend_Email_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Handler_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'components' => array(
				'activity_log' => array(
					'endpoints' => array(
						Resend_Email_Endpoint::ACTION_NAME => array(
							'action' => array(
								'value'   => Resend_Email_Endpoint::ACTION_NAME,
								'default' => 'mock_endpoint',
							),
							'nonce'  => array(
								'value'   => wp_create_nonce( Resend_Email_Endpoint::ACTION_NAME ),
								'default' => 'nonce',
							),
						),
					),
				),
			),
		);
	}

}
