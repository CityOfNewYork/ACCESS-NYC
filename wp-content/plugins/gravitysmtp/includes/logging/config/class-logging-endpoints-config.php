<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Config;

use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Debug_Logs_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Email_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Events_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Email_Message_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Paginated_Debug_Log_Items_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Paginated_Items_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Log_Item_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Logging_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Delete_Email_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Delete_Email_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Delete_Email_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Delete_Events_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Delete_Events_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Delete_Events_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Get_Email_Message_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Get_Email_Message_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Get_Email_Message_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					'activity_log_page' => array(
						'action' => array(
							'value'   => Get_Paginated_Items_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Get_Paginated_Items_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					'debug_log_page' => array(
						'action' => array(
							'value'   => Get_Paginated_Debug_Log_Items_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Get_Paginated_Debug_Log_Items_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Delete_Debug_Logs_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Delete_Debug_Logs_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Delete_Debug_Logs_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Log_Item_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Log_Item_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Log_Item_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				),
			),
		);
	}

}
