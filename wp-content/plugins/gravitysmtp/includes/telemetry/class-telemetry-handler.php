<?php

namespace Gravity_Forms\Gravity_SMTP\Telemetry;

use Gravity_Forms\Gravity_Tools\Logging\Logger;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Telemetry_Handler {

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var Telemetry_Snapshot_Data
	 */
	protected $snapshot_data;

	/**
	 * @var Telemetry_Background_Processor
	 */
	protected $processor;

	public function __construct( $logger, $snapshot_data, $processor ) {
		$this->logger        = $logger;
		$this->snapshot_data = $snapshot_data;
		$this->processor     = $processor;
	}

	public function handle() {
		// Only run once a week.
		$last_run     = get_option( 'gravitysmtp_last_telemetry_run', 0 );
		$current_time = time();

		if ( $current_time - $last_run < WEEK_IN_SECONDS ) {
			return;
		}

		update_option( 'gravitysmtp_last_telemetry_run', $current_time );

		$this->logger->log_debug( __METHOD__ . sprintf( '(): Enqueuing telemetry batches' ) );

		$this->snapshot_data->record_data();

		$all_data = $this->snapshot_data->get_existing_data();
		$snapshot = $all_data['snapshot'];

		// Enqueue the snapshot first, alone, to be sent to its own endpoint.
		$this->processor->push_to_queue( $snapshot );
		$this->processor->save()->dispatch();

		// Clear saved telemetry data except the snapshot.
		update_option(
			$this->snapshot_data->data_setting_name,
			array(
				'snapshot' => $snapshot,
				'events'   => array(),
			),
			false
		);
	}

}