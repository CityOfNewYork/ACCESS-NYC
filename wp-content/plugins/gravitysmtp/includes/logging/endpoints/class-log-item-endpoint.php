<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_Tools\Logging\File_Logging_Provider;

class Log_Item_Endpoint extends Endpoint {

	const PARAM_LOG_MESSAGE = 'message';
	const PARAM_PRIORITY    = 'priority';

	const ACTION_NAME = 'log_debug_item';

	/**
	 * @var Debug_Logger
	 */
	protected $logger;

	public function __construct( Debug_Logger $logger ) {
		$this->logger = $logger;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( __( 'Missing required parameters.', 'gravitysmtp' ), 400 );
		}

		$message  = filter_input( INPUT_POST, self::PARAM_LOG_MESSAGE );
		$priority = filter_input( INPUT_POST, self::PARAM_PRIORITY );

		if ( ! empty( $message ) ) {
			$message = htmlspecialchars( $message );
		}

		$this->logger->log( $message, $priority );

		wp_send_json_success( array( 'message' => __( 'Message logged', 'gravitysmtp' ) ), 200 );
	}

	protected function validate() {
		check_ajax_referer( $this->get_nonce_name(), 'security' );

		if ( empty( $_REQUEST[ self::PARAM_LOG_MESSAGE ] ) ) {
			return false;
		}

		return true;
	}

}
