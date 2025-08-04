<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Debug;

use Gravity_Forms\Gravity_Tools\Logging\Logger;

class Null_Logger extends Logger {

	protected function should_log() {
		return false;
	}

	protected function delete_log() {
		return;
	}

}