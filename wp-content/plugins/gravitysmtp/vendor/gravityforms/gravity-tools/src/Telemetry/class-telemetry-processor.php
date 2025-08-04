<?php

namespace Gravity_Forms\Gravity_Tools\Telemetry;

use Gravity_Forms\Gravity_Tools\Background_Processing\Background_Process;

/**
 * Telemetry_Processor Class.
 */
abstract class Telemetry_Processor extends Background_Process {

	/**
	 * @var string
	 */
	const TELEMETRY_ENDPOINT = 'https://in.gravity.io/';

	/**
	 * @var string
	 */
	protected $action = 'telemetry_processor';

	/**
	 * Send data to the telemetry endpoint.
	 *
	 * @since 1.0
	 *
	 * @param array  $entries The data to send.
	 *
	 * @return array|WP_Error
	 */
	abstract public function send_data( $entries );

	/**
	 * Task
	 *
	 * Process a single batch of telemetry data.
	 *
	 * @param mixed $batch
	 * @return mixed
	 */
	protected function task( $batch ) {

		if ( ! is_array( $batch ) ) {
			$batch = array( $batch );
		}

		$raw_response = null;
		$this->logger->log_debug( __METHOD__ . sprintf( '(): Processing a batch of %d telemetry data.', count( $batch ) ) );
		$raw_response = $this->send_data( $batch );

		if ( is_wp_error( $raw_response ) ) {
			$this->logger->log_debug( __METHOD__ . sprintf( '(): Failed sending telemetry data. Code: %s; Message: %s.', $raw_response->get_error_code(), $raw_response->get_error_message() ) );
			return false;
		}

		foreach ( $batch as $item ) {
			/**
			 * @var Telemetry_Data $item
			 */
			if ( ! is_object( $item ) ) {
				$this->logger->log_debug( __METHOD__ . sprintf( '(): Telemetry data is missing. Aborting running data_sent method on this entry.' ) );
				continue;
			}

			$item->after_send( $raw_response );
		}

		return false;
	}
}
