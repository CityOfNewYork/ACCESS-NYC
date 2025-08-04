<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Log;

use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;

class Logger {


	/**
	 * @var Log_Details_Model
	 */
	protected $logs;

	public function __construct( $logs ) {
		$this->logs = $logs;
	}

	public function log( $email_id, $action_name, $log_value ) {
		return $this->logs->create( $email_id, $action_name, $log_value );
	}

}