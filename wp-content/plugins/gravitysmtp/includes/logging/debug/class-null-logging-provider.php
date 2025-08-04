<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Debug;

use Gravity_Forms\Gravity_Tools\Logging\Logging_Provider;

class Null_Logging_Provider implements Logging_Provider {

	public function log_info( $line ) {
		return;
	}

	public function log_debug( $line ) {
		return;
	}

	public function log_warning( $line ) {
		return;
	}

	public function log_error( $line ) {
		return;
	}

	public function log_fatal( $line ) {
		return;
	}

	public function log( $line, $priority ) {
		return;
	}

	public function delete_log() {
		return;
	}

	public function write_line_to_log( $line ) {
		return;
	}

	public function get_lines() {
		return;
	}
}