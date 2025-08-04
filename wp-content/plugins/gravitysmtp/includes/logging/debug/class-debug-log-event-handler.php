<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Debug;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\View_Log_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;

class Debug_Log_Event_Handler {

	const DEBUG_LOG_ENABLE_TIME = 'debug_log_enable_time';

	/**
	 * @var Debug_Logger
	 */
	protected $debug_logger;

	/**
	 * @var Data_Store_Router
	 */
	protected $data;

	public function __construct( Debug_Logger $debug_logger, Data_Store_Router $data ) {
		$this->debug_logger = $debug_logger;
		$this->data = $data;
	}

	public function on_setting_update( $setting, $value ) {
		if ( $setting !== Tools_Config::SETTING_DEBUG_LOG_ENABLED ) {
			return;
		}

		// Delete verification key when disabled.
		if ( $value === false || $value === 0 || $value === '0' || $value === 'false' ) {
			delete_option( View_Log_Endpoint::OPTION_VERIFICATION_KEY );
			delete_option( self::DEBUG_LOG_ENABLE_TIME );

			$delete_log_on_deactivate = apply_filters( 'gravitysmtp_delete_log_on_deactivate', false );

			if ( $delete_log_on_deactivate ) {
				$this->debug_logger->delete_log();
			}

			return;
		}

		// Refresh verficiation key when enabled.
		$bytes  = random_bytes( 12 );
		$random = bin2hex( $bytes );

		update_option( View_Log_Endpoint::OPTION_VERIFICATION_KEY, $random );
		update_option( self::DEBUG_LOG_ENABLE_TIME, time() );
	}

	public function on_retention_cron() {
		$enabled = get_option( self::DEBUG_LOG_ENABLE_TIME );

		if ( empty( $enabled ) ) {
			return;
		}

		$period_in_days = $this->data->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_RETENTION, 7 );

		$now     = date_create( 'now' );
		$enabled = date_create( date( 'Y-m-d H:i:s', $enabled ) );

		$interval = date_diff( $now, $enabled );
		$diff = $interval->format( '%a' );

		if ( (int) $diff < (int) $period_in_days ) {
			return;
		}

		$this->debug_logger->delete_log();
	}
}
