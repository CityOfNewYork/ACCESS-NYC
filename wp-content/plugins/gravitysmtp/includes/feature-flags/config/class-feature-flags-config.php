<?php

namespace Gravity_Forms\Gravity_SMTP\Feature_Flags\Config;

use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_Tools\Config;

class Feature_Flags_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function should_enqueue() {
		return is_admin();
	}

	public function data() {
		return array(
			'common' => array(
				'feature_flags' => array(
					'data' => array(
						'all'      => Feature_Flag_Manager::flags(),
						'enabled'  => Feature_Flag_Manager::enabled_flags(),
						'statuses' => Feature_Flag_Manager::flags_by_status(),
					),
				),
			),
		);
	}

}
