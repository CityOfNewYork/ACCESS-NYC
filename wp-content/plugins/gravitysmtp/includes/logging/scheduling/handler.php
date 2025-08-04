<?php

namespace Gravity_Forms\Gravity_SMTP\Logging\Scheduling;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Handler {

	/**
	 * @var Data_Store_Router
	 */
	protected $plugin_data;

	/**
	 * @var Event_Model
	 */
	protected $emails;

	/**
	 * @var Log_Details_Model
	 */
	protected $logs;

	public function __construct( $plugin_data_store, $email_model, $log_model ) {
		$this->plugin_data = $plugin_data_store;
		$this->emails      = $email_model;
		$this->logs        = $log_model;
	}

	/**
	 * Run the log retention functions. Currently called by a cron job.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function run_log_retention() {
		$retention   = $this->plugin_data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION, 0 );
		$max_records = $this->plugin_data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_MAX_EVENT_RECORDS, 0 );

		// Retention is set to never delete, or we have a missing value, Bail.
		if ( ! Booliesh::get( $retention ) && ! Booliesh::get( $max_records ) ) {
			return;
		}

		$to_delete = $this->get_items_to_delete( $retention, $max_records );

		// No items to delete, bail.
		if ( empty( $to_delete ) ) {
			return;
		}

		$this->delete_expired_items( $to_delete );
	}

	/**
	 * Get the items to delete by the retention date.j
	 *
	 * @since 1.0
	 *
	 * @param int $retention
	 *
	 * @return array
	 */
	private function get_items_to_delete( $retention, $max_records ) {

		if ( Booliesh::get( $max_records ) ) {
			$items = $this->emails->get_records_over_limit( $max_records );
		} else {
			$interval_string = sprintf( ' - %d days', $retention );
			$newDate         = gmdate( 'Y-m-d H:i:s', strtotime( $interval_string ) );

			$params = array(
				array(
					'date_created',
					'<=',
					$newDate
				)
			);

			$items = $this->emails->find( $params );
		}

		$ids = wp_list_pluck( $items, 'id' );

		return $ids;
	}

	/**
	 * Delete expired items by id.
	 *
	 * @since 1.0
	 *
	 * @param array $items
	 *
	 * @return true|\WP_Error
	 */
	private function delete_expired_items( $items ) {
		try {
			foreach ( $items as $id ) {
				$this->emails->delete( $id );
				$this->logs->delete_by_event_id( $id );
			}

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error( 'delete_failed', $e->getMessage() );
		}
	}

}