<?php

namespace Gravity_Forms\Gravity_SMTP\Environment;

class Environment_Details {

	public function get_min() {
		return defined( 'GRAVITYSMTP_SCRIPT_DEBUG' ) && GRAVITYSMTP_SCRIPT_DEBUG ? '' : '.min';
	}

	public function get_version() {
		return defined( 'GRAVITYSMTP_SCRIPT_DEBUG' ) && GRAVITYSMTP_SCRIPT_DEBUG ? time() : time();
	}

}
