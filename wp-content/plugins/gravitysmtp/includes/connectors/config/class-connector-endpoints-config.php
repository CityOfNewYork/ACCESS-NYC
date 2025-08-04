<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Config;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Get_Single_Email_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Cleanup_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Migrate_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Send_Test_Endpoint;
use Gravity_Forms\Gravity_Tools\Config;

class Connector_Endpoints_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'endpoints' => array(
					Send_Test_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Send_Test_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Send_Test_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Save_Connector_Settings_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Save_Connector_Settings_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Save_Connector_Settings_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Save_Plugin_Settings_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Save_Plugin_Settings_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Save_Plugin_Settings_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Get_Single_Email_Data_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Get_Single_Email_Data_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Get_Single_Email_Data_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
					Cleanup_Data_Endpoint::ACTION_NAME => array(
						'action' => array(
							'value'   => Cleanup_Data_Endpoint::ACTION_NAME,
							'default' => 'mock_endpoint',
						),
						'nonce'  => array(
							'value'   => wp_create_nonce( Cleanup_Data_Endpoint::ACTION_NAME ),
							'default' => 'nonce',
						),
					),
				)
			),
		);
	}

}
