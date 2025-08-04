<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Debug;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Logging\DB_Logging_Provider;
use Gravity_Forms\Gravity_Tools\Logging\Logger;
use Gravity_Forms\Gravity_Tools\Logging\Logging_Provider;

class Debug_Logger extends Logger {

	protected $log_level;

	protected $priority_map = array(
		'debug'   => DB_Logging_Provider::DEBUG,
		'info'    => DB_Logging_Provider::INFO,
		'warning' => DB_Logging_Provider::WARN,
		'error'   => DB_Logging_Provider::ERROR,
		'fatal'   => DB_Logging_Provider::FATAL,
	);

	public function __construct( Logging_Provider $provider, $log_level ) {
		parent::__construct( $provider );
		$this->log_level = $log_level;
	}

	public static function log_message( $message, $priority ) {
		$container = Gravity_SMTP::$container;
		/**
		 * @var self $logger
		 */
		$logger = $container->get( Logging_Service_Provider::DEBUG_LOGGER );

		$logger->log( $message, $priority );
	}

	public function should_log( $log_level = 'all' ) {
		$container = Gravity_SMTP::container();
		$data      = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$enabled   = $data->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_ENABLED, false );

		if ( $enabled === false || $enabled === 0 || $enabled === '0' || $enabled === 'false' ) {
			return false;
		}

		if ( $log_level === 'all' ) {
			return true;
		}

		$level_prio = $this->map_priority_string( $log_level );

		return $level_prio >= $this->log_level;
	}

	public function delete_log() {
		$this->provider->delete_log();
	}

	public function map_priority_string( $priority_string ) {
		if ( isset( $this->priority_map[ $priority_string ] ) ) {
			return $this->priority_map[ $priority_string ];
		}

		return DB_Logging_Provider::DEBUG;
	}

	public function get_log_items() {
		return $this->provider->get_lines();
	}
}