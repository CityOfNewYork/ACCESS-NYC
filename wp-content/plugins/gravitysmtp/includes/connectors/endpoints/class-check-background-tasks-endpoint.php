<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors\Endpoints;

use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;

class Check_Background_Tasks_Endpoint extends Endpoint {

	const ACTION_NAME = 'gravitysmtp_check_background_tasks';

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( $this->validate() ) {
			echo 'ok';
		}
		die();
	}

}
