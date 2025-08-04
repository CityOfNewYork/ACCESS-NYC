<?php

namespace Gravity_Forms\Gravity_SMTP\Errors;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Log\Logger;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;

class Error_Handler {

	/**
	 * @var Log_Details_Model
	 */
	private $logger;

	/**
	 * @var Event_Model
	 */
	private $events;

	/**
	 * @var Debug_Logger
	 */
	private $debug_logger;

	public function __construct( Logger $logger, Event_Model $events, Debug_Logger $debug_logger ) {
		$this->logger       = $logger;
		$this->events       = $events;
		$this->debug_logger = $debug_logger;
	}

	public function handle() {
		$error = error_get_last();

		if ( ! $error ) {
			return;
		}

		if ( ! in_array( $error['type'], array( E_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_ERROR ) ) ) {
			return;
		}

		$email_id = $this->events->get_latest_id();

		if ( is_null( $email_id ) ) {
			return;
		}

		$error_prefix = __( 'A fatal error occured when sending', 'gravitysmtp' );

		$this->logger->log( $email_id, 'failed', sprintf( '%s: %s', $error_prefix, $error['message'] ) );

		$this->events->update( array( 'status' => 'failed' ), $email_id );

		$this->debug_logger->log_fatal( $error['message'] );

		if ( wp_doing_ajax() ) {
			wp_send_json_error( sprintf( '%s: %s', $error_prefix, $error['message'] ), 500 );
		}
	}

}