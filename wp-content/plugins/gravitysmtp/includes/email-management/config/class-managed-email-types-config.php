<?php

namespace Gravity_Forms\Gravity_SMTP\Email_Management\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Email_Management\Email_Management_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Managed_Email_Types_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		$page = filter_input( INPUT_GET, 'page', FILTER_DEFAULT );

		return is_admin() && $page === 'gravitysmtp-settings';
	}

	public function data() {
		$container = Gravity_SMTP::container();
		$stopper = $container->get( Email_Management_Service_Provider::EMAIL_STOPPER );

		return array(
			'components' => array(
				'settings' => array(
					'data' => array(
						'managed_email_types' => $stopper->get_settings_info(),
					),
				),
			),
		);
	}

}
