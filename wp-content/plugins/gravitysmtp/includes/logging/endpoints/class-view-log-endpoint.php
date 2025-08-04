<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Endpoints;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_Tools\Logging\File_Logging_Provider;

class View_Log_Endpoint extends Endpoint {

	const PARAM_LOG_KEY = 'key';

	const OPTION_VERIFICATION_KEY = 'gravitysmtp_log_verification_key';

	const ACTION_NAME = 'view_debug_log';

	/**
	 * @var Data_Store_Router
	 */
	protected $data;

	/**
	 * @var Debug_Logger $debug_logger
	 */
	protected $debug_logger;

	/**
	 * @var Debug_Log_Model $model
	 */
	protected $model;

	public function __construct( Data_Store_Router $data, $debug_logger, $debug_model ) {
		$this->data = $data;
		$this->debug_logger = $debug_logger;
		$this->model = $debug_model;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			echo __( 'Unauthorized', 'gravitysmtp' );
			die();
		}

		$lines = $this->debug_logger->get_log_items();
		echo '<pre style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word;">';
		foreach ( $lines as $index => $item ) {
			printf( "%s %s - %s -->    %s", str_pad( '[' . $item->id() . ']', 8, ' ' ), $item->timestamp(), strtoupper( str_pad( $item->priority(), 7, ' ' ) ), $item->line() );
			if ( $index < count( $lines ) - 1 ) {
				echo '</pre><hr><pre style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word;">';
			}
		}
		echo '</pre>';
		die();
	}

	protected function validate() {
		// Ensure logging is enabled.
		$enabled = $this->data->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_ENABLED, false );

		if ( ! $enabled ) {
			return false;
		}

		// Get the passed verficiation key; bail if empty.
		$key = filter_input( INPUT_GET, self::PARAM_LOG_KEY );
		if ( empty( $key ) ) {
			return false;
		}

		// Get the stored verification key; if it is empty or does not match the provided key, bail.
		$key   = htmlspecialchars( $key );
		$check = get_option( self::OPTION_VERIFICATION_KEY );

		if ( empty( $check ) || $check !== $key ) {
			return false;
		}

		return true;
	}

}
