<?php

namespace Gravity_Forms\Gravity_SMTP\Routing\Handlers;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Enums\Connector_Status_Enum;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;

class Primary_Backup_Handler implements Routing_Handler {

	private static $primary_attempted = false;
	private static $backup_attempted  = false;

	/**
	 * @var Data_Store_Router
	 */
	protected $data_router;

	/**
	 * @var Debug_Logger
	 */
	protected $logger;

	public function __construct( $data_router, $logger ) {
		$this->data_router = $data_router;
		$this->logger = $logger;
	}

	private function error_prefix() {
		return sprintf( '%s:: ', str_replace( __NAMESPACE__, '', __CLASS__ ) );
	}

	public function reset() {
		self::$primary_attempted = false;
		self::$backup_attempted  = false;
	}

	public function handle( $current_connector, $email_data ) {

		// A backup was already attempted; no more connections to check, return false.
		if ( self::$backup_attempted ) {
			$this->logger->log_warning( $this->error_prefix() .  __( 'Primary and Backup integrations failed to send. Aborting email send.', 'gravitysmtp' ) );
			return false;
		}

		// We've tried the primary connection, now look for a backup connection.
		if ( self::$primary_attempted ) {
			self::$backup_attempted = true;
			$type                   = $this->data_router->get_connector_status_of_type( Connector_Status_Enum::BACKUP );

			if ( ! $type ) {
				$this->logger->log_warning( $this->error_prefix() . __( 'Backup integration not set, aborting email send.', 'gravitysmtp' ) );
				return false;
			}

			$enabled = $this->data_router->get_setting( $type, Connector_Base::SETTING_ENABLED, false );

			/* translators: %1$s: integration type */
			$this->logger->log_debug( $this->error_prefix() . sprintf( __( 'Secondary integration identified: %1$s', 'gravitysmtp' ), $type ) );

			if ( $enabled ) {
				return $type;
			}

			$this->logger->log_debug( $this->error_prefix() . __( 'Secondary integration not enabled. Skipping.', 'gravitysmtp' ) );

			return false;
		}

		// This is the first call, so attempt to use the primary connection.
		self::$primary_attempted = true;
		$type                    = $this->data_router->get_connector_status_of_type( Connector_Status_Enum::PRIMARY );

		if ( ! $type ) {
			$this->logger->log_warning( $this->error_prefix() .  __( 'Primary integration not set, checking for Backup integration.', 'gravitysmtp' ) );
			return $this->handle( $type, $email_data );
		}

		$enabled = $this->data_router->get_setting( $type, Connector_Base::SETTING_ENABLED, false );

		/* translators: %1$s: integration type */
		$this->logger->log_debug( $this->error_prefix() .  sprintf( __( 'Primary integration identified: %1$s', 'gravitysmtp' ), $type ) );

		// Primary connection not enabled; immediately try backup connection.
		if ( ! $enabled ) {
			$this->logger->log_warning( $this->error_prefix() .  __( 'Primary integration not enabled, moving on to Backup integration.', 'gravitysmtp' ) );
			return $this->handle( $type, $email_data );
		}

		// Primary connection found and is enabled.
		return $type;
	}

}
