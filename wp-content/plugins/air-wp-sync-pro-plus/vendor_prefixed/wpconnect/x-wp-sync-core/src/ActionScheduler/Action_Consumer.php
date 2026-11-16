<?php
/**
 * Action Scheduler Action Consumer.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\ActionScheduler;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Action_Consumer
 */
class Action_Consumer {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'airwpsync_process_records', array( $this, 'consume' ), 10, 3 );
		add_action( 'airwpsync_post_process_job', array( $this, 'consume_post_process_job' ), 10, 3 );
	}

	/**
	 * Consume a record from queue
	 *
	 * @param int    $importer_id Importer post id.
	 * @param string $run_id Importer run id.
	 * @param string $item_id Records chunk id.
	 */
	public function consume( $importer_id, $run_id, $item_id ) {
		// Get importer instance from id.
		$importer = Helper::get_importer_by_id( $importer_id );
		if ( ! $importer ) {
			return;
		}
		// Get Remote record saved as a temporary option.
		$records = get_option( $item_id );
		if ( ! $records ) {
			return;
		}

		foreach ( $records as $record ) {
			// Import record.
			list($content_id, $error) = $importer->process_remote_record( $record );
			// Temporarily save created or saved content ID.
			if ( ! empty( $content_id ) ) {
				$importer->append_run_result( $content_id, $run_id );
			}
			if ( is_wp_error( $error ) ) {
				$importer->add_error( $error );
			}
		}

		// Delete temporary option.
		delete_option( $item_id );
	}

	/**
	 * Consume a post process job from queue
	 *
	 * @param int    $importer_id Importer post id.
	 * @param string $run_id Importer run id.
	 * @param int    $jobs_chunk_index Index of the chunk (from "post-process-jobs" run option).
	 */
	public function consume_post_process_job( $importer_id, $run_id, $jobs_chunk_index ) {
		// Get importer instance from id.
		$importer = Helper::get_importer_by_id( $importer_id );
		if ( ! $importer ) {
			return;
		}
		$post_process_jobs = $importer->get_run_option( $run_id, 'post-process-jobs', array() );
		if ( count( $post_process_jobs ) > 0 ) {
			$chunks = array_chunk( $post_process_jobs, 100 );
			$jobs   = $chunks[ $jobs_chunk_index ];
			if ( ! is_array( $jobs ) ) {
				$jobs = array();
			}
		}

		$post_process_jobs_option_key = 'airwpsync-' . $importer_id . '-run-' . $run_id . '-post-process-jobs';
		foreach ( $jobs as $job ) {
			list($record, $fields) = get_option( $post_process_jobs_option_key . '-' . $job[0] );

			/**
			 * Trigger "airwpsync/run_post_process_job" action.
			 *
			 * @param Abstract_Importer $importer Importer.
			 * @param \stdClass $record Remote record.
			 * @param array $fields Record fields.
			 * @param mixed $existing_object_id WordPress object id.
			 * @param string $job_key Job key.
			 * @param array $args Job args.
			 */
			do_action(
				'airwpsync/run_post_process_job',
				$importer,
				$record,
				$fields,
				$job[1],
				$job[2],
				$job[3] ?? array()
			);
		}
	}
}
