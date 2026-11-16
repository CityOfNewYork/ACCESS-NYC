<?php
/**
 * Connection API: trigger update, get progress, cancel import.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Admin_Connection_Api
 */
class Admin_Connection_Api {

	/**
	 * Register API endpoints.
	 */
	public function __construct() {
		add_action( 'wp_ajax_air_wp_sync_connection_api_trigger_update', array( $this, 'trigger_update' ) );
		add_action( 'wp_ajax_air_wp_sync_connection_api_get_progress', array( $this, 'get_progress' ) );
		add_action( 'wp_ajax_air_wp_sync_connection_api_cancel_import', array( $this, 'cancel_import' ) );
		add_action( 'wp_ajax_air_wp_sync_connection_api_clear_cache', array( $this, 'clear_cache' ) );
	}

	/**
	 * Manual sync AJAX function
	 *
	 * @throws \Exception No connection found.
	 * @throws \Exception Error from importer.
	 */
	public function trigger_update() {
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_trigger_update', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$importer_id = (int) $_POST['importer_id'] ?? 0;

		try {
			$importer = Helper::get_importer_by_id( $importer_id );
			if ( ! $importer ) {
				throw new \Exception( 'No connection found.' );
			}
			// Get Remote records and add theme to queue.
			$result = $importer->run();
			if ( is_wp_error( $result ) ) {
				throw new \Exception( $result->get_error_message() );
			}

			// Unlock Action Scheduler, force queue to start now.
			delete_option( 'action_scheduler_lock_async-request-runner' );

			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $importer_id, $e->getMessage() ) );
		}
	}

	/**
	 * Get sync progress
	 *
	 * @throws \Exception No connection found.
	 */
	public function get_progress() {
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_get_progress', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$importer_id = (int) $_POST['importer_id'] ?? 0;

		try {
			$importer = Helper::get_importer_by_id( $importer_id );
			if ( ! $importer ) {
				throw new \Exception( 'No connection found.' );
			}

			if ( $importer->get_run_id() ) {
				$importer->check_import_state();
				$as_actions_args          = array(
					'importer_id' => $importer_id,
					'run_id'      => $importer->get_run_id(),
				);
				$pending_running_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING );
				$actions_remaining        = array_merge(
					Helper::get_as_actions( 'airwpsync_process_records', $pending_running_statuses, $as_actions_args ),
					Helper::get_as_actions( 'airwpsync_importer_post_process', $pending_running_statuses, $as_actions_args ),
					Helper::get_as_actions( 'airwpsync_post_process_job', $pending_running_statuses, $as_actions_args )
				);

				$all_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_COMPLETE, \ActionScheduler_Store::STATUS_CANCELED, \ActionScheduler_Store::STATUS_FAILED );
				$all_actions  = array_merge(
					Helper::get_as_actions( 'airwpsync_process_records', $all_statuses, $as_actions_args ),
					Helper::get_as_actions( 'airwpsync_importer_post_process', $all_statuses, $as_actions_args ),
					Helper::get_as_actions( 'airwpsync_post_process_job', $all_statuses, $as_actions_args )
				);
			}

			if ( ! $importer->get_run_id() ) {
				if ( $importer->get_status() === 'error' ) {
					wp_send_json_error(
						array_merge(
							$this->get_stats( $importer_id ),
							array(
								'ratio'             => 1,
								'remaining_minutes' => 0,
							)
						)
					);
				} else {
					wp_send_json_success(
						array_merge(
							$this->get_stats( $importer_id ),
							array(
								'ratio'             => 1,
								'remaining_minutes' => 0,
							)
						)
					);
				}
			} else {
				$finished_actions = count( $all_actions ) - count( $actions_remaining );
				$progress_ratio   = count( $all_actions ) > 0 ? $finished_actions / count( $all_actions ) : 1;

				$extra      = array(
					'ratio'             => min( 0.99, $progress_ratio ), // it can't be completed here because we have a run id.
					'remaining_minutes' => $progress_ratio < 1 ? count( $actions_remaining ) : 0, // let's assume 1 min per chunk.
				);
				$start_date = get_option( 'airwpsync-' . $importer_id . '-run-' . $importer->get_run_id() . '-start-date' );

				$count_other_pending_actions = 0;
				if ( $start_date ) {
					$time_spent          = human_time_diff( strtotime( $start_date ), time() );
					$extra['time_spent'] = $time_spent;
					// Override ratio based on time spent.
					if ( count( $all_actions ) > 0 ) {
						$total_minutes  = count( $all_actions );
						$extra['ratio'] = ( time() - strtotime( $start_date ) ) / 60 / $total_minutes;
					}

					// Make sure ration is not bigger than 1.
					$extra['ratio'] = min( 1, $extra['ratio'] );

					// We try to check if they are long pending actions that was planned before we start the sync.
					$all_pending_actions_before_sync_started = Helper::get_as_pending_actions(
						array(
							'date'         => new \DateTime( $start_date ),
							'date_compare' => '<=',
						)
					);
					$other_pending_actions                   = array_diff( $all_pending_actions_before_sync_started, $all_actions );
					$other_pending_actions                   = array_values( $other_pending_actions );
					$count_other_pending_actions             = count( $other_pending_actions );
				}
				$extra['as_other_pending_actions'] = $count_other_pending_actions;
				wp_send_json_success( array_merge( $this->get_stats( $importer_id ), $extra ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $importer_id, $e->getMessage() ) );
		}
	}

	/**
	 * Cancel import
	 *
	 * @throws \Exception No connection found.
	 */
	public function cancel_import() {
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_cancel_import', 'nonce' );
		Helper::check_ajax_admin_user_access();

		$importer_id = (int) $_POST['importer_id'] ?? 0;

		try {
			$importer = Helper::get_importer_by_id( $importer_id );
			if ( ! $importer ) {
				throw new \Exception( 'No connection found.' );
			}

			$importer->end_run( 'cancel' );

			wp_send_json_success( $this->get_stats( $importer_id ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( $this->get_stats( $importer_id, $e->getMessage() ) );
		}
	}

	/**
	 * Get importer statistics
	 *
	 * @param int         $importer_id Importer id.
	 * @param null|string $forced_error Error to be displayed.
	 *
	 * @return array
	 */
	protected function get_stats( $importer_id, $forced_error = null ) {
		$importer     = Helper::get_importer_by_id( $importer_id );
		$status       = $forced_error ? 'error' : ( $importer ? $importer->get_status() : '' );
		$next_sync    = $importer_id ? wp_next_scheduled( 'air_wp_sync_importer_' . $importer_id ) : '';
		$last_updated = $importer ? $importer->get_last_updated() : '';
		$errors       = array();
		if ( $forced_error ) {
			$errors [] = $forced_error;
		} elseif ( $importer ) {
			$errors = $importer->get_errors();
		}
		// Make sure we have only error as string here.
		$errors = Helper::errors_as_string( $errors );

		$time_spent      = '';
		$count_processed = -1;
		$count_deleted   = -1;
		$start_date      = null;
		if ( 'success' === $status ) {
			$start_date      = get_post_meta( $importer_id, 'last_start_date', true );
			$count_processed = get_post_meta( $importer_id, 'count_processed', true );
			if ( ! is_numeric( $count_processed ) ) {
				$count_processed = -1;
			}
			$count_deleted = get_post_meta( $importer_id, 'count_deleted', true );
			if ( ! is_numeric( $count_deleted ) ) {
				$count_deleted = -1;
			}
		}
		if ( $start_date && $last_updated ) {
			$time_spent = human_time_diff( strtotime( $start_date ), strtotime( $last_updated ) );
		}

		return array(
			'status'          => $status,
			'errors'          => $errors,
			'last_updated'    => $last_updated,
			'next_sync'       => $next_sync,
			'time_spent'      => $time_spent,
			'count_processed' => (int) $count_processed,
			'count_deleted'   => (int) $count_deleted,
			'logs_url'        => $importer ? $importer->get_latest_log_file_url() : '',
		);
	}

	/**
	 * Clear remote API cache.
	 *
	 * @return void
	 */
	public function clear_cache() {
		// Nonce check.
		check_ajax_referer( 'air_wp_sync_connection_api_clear_cache', 'nonce' );
		Helper::check_ajax_admin_user_access();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transient_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT options.option_name FROM {$wpdb->options} options
			WHERE options.option_name LIKE %s",
				$wpdb->esc_like( '_transient_airwpsync_tables_' ) . '%'
			)
		);
		foreach ( $transient_names as $transient_name ) {
			delete_transient( str_replace( '_transient_', '', $transient_name ) );
		}

		do_action( 'airwpsync/api_client_cache_cleared' );

		wp_send_json_success();
	}
}
