<?php
/**
 * Abstract Importer.
 * Base class to define custom importer (e.g post, user importer).
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers;

use DateTime, DateTimeZone, DateInterval;
use Exception;
use WP_Error, WP_CLI;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Abstract_Module;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Importer_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Logger;

/**
 * Abstract Importer
 */
abstract class Abstract_Importer {
	/**
	 * Infos
	 *
	 * @var Importer_Settings
	 */
	public $infos;

	/**
	 * Config
	 *
	 * @var Importer_Settings
	 */
	public $config;

	/**
	 * Module
	 *
	 * @var Abstract_Module
	 */
	protected $module;

	/**
	 * Remote data provider (to get field and records).
	 *
	 * @var Importer_Remote_Data_Provider
	 */
	protected $remote_data_provider;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected $logger;


	const STATE_INIT               = 'STATE_INIT';
	const STATE_PROCESSING_RECORDS = 'STATE_PROCESSING_RECORDS';

	const STATE_PROCESSING_POST_JOBS = 'STATE_PROCESSING_POST_JOBS';

	const STATE_COMPLETED = 'STATE_COMPLETED';

	/**
	 * Constructor
	 *
	 * @param \WP_Post        $importer_post_object Post object holding the importer config.
	 * @param Abstract_Module $module Importer Module.
	 * @param callable        $remote_data_provider_factory Factory for Remote data provider (to get field and records).
	 * @param Logger          $logger Logger.
	 */
	public function __construct( $importer_post_object, $module, $remote_data_provider_factory, $logger ) {
		$this->module = $module;
		$this->logger = $logger;
		$this->load_settings( $importer_post_object );
		$this->remote_data_provider = $remote_data_provider_factory( $this );

		add_filter( 'airwpsync/get_importers', array( $this, 'register' ) );
		$this->init();
		$this->schedule_cron_event();
	}

	/**
	 * Register importer
	 *
	 * @param Abstract_Importer[] $importers Registered importers.
	 *
	 * @return Abstract_Importer[] $importers Registered importers.
	 */
	public function register( $importers ) {
		$importers[] = $this;
		return $importers;
	}

	/**
	 * Infos getter
	 *
	 * @return Importer_Settings;
	 */
	public function infos() {
		return $this->infos;
	}

	/**
	 * Config getter
	 *
	 * @return Importer_Settings
	 */
	public function config() {

		return $this->config;
	}

	/**
	 * Return importer status.
	 *
	 * @return string|false
	 */
	public function get_status() {
		return get_post_meta( $this->infos()->get( 'id' ), 'status', true );
	}

	/**
	 * Return last updated date as string (gmdate + format 'Y-m-d H:i:s') or false if there were no successful sync yet.
	 *
	 * @return string|false
	 */
	public function get_last_updated() {
		return get_post_meta( $this->infos()->get( 'id' ), 'last_updated', true );
	}

	/**
	 * Scheduled Sync next getter
	 *
	 * @return int|false
	 */
	public function get_next_scheduled_sync() {
		return wp_next_scheduled( $this->get_schedule_slug() );
	}

	/**
	 * Fields getter
	 *
	 * @return array|false|null
	 */
	public function get_remote_fields() {
		return $this->get_run_option( $this->get_run_id(), 'table_fields', array() );
	}

	/**
	 * Run ID getter
	 *
	 * @return string|false|null
	 */
	public function get_run_id() {
		return get_post_meta( $this->infos()->get( 'id' ), 'run', true );
	}

	/**
	 * Get run option key.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 *
	 * @return string
	 */
	protected function get_run_option_key( $run_id, $suffix ) {
		return 'airwpsync-' . $this->infos()->get( 'id' ) . '-run-' . $run_id . '-' . $suffix;
	}

	/**
	 * Update run option.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	protected function update_run_option( $run_id, $suffix, $value ) {
		update_option( $this->get_run_option_key( $run_id, $suffix ), $value, false );
	}

	/**
	 * Get run option.
	 *
	 * @param string $run_id Run id.
	 * @param string $suffix Key suffix.
	 * @param mixed  $default_value Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed Value of the option.
	 */
	public function get_run_option( $run_id, $suffix, $default_value = false ) {
		return get_option( $this->get_run_option_key( $run_id, $suffix ), $default_value );
	}

	/**
	 * Cron action
	 *
	 * @return bool|WP_Error
	 */
	public function cron() {
		if ( $this->get_run_id() ) {
			$this->log( esc_html__( 'A sync should have been started by a cron job but it has been skipped, a sync is already running.', 'air-wp-sync-pro-plus' ), 'warning' );
			return false;
		}
		return $this->run();
	}

	/**
	 * Run importer
	 *
	 * @return bool|WP_Error
	 */
	public function run() {
		if ( $this->get_run_id() ) {
			return new WP_Error( 'air-wp-sync-pro-plus-run-error', __( 'A sync is already running.', 'air-wp-sync-pro-plus' ) );
		}

		$this->reset_errors();

		try {
			// Define a unique id for this run.
			$run_id = uniqid();
			// Save run.
			update_post_meta( $this->infos()->get( 'id' ), 'run', $run_id );

			$this->log( sprintf( 'Starting importer...' ) );

			update_option( 'airwpsync-' . $this->infos()->get( 'id' ) . '-run-' . $run_id . '-start-date', gmdate( 'Y-m-d H:i:s' ), false );

			$this->update_run_option( $run_id, 'state', self::STATE_INIT );

			// Save table schema.
			$this->update_run_option( $run_id, 'table_fields', $this->get_table_fields() );

			// Loop through all pages.
			$offset = null;
			do {
				wp_cache_delete( $this->infos()->get( 'id' ), 'post_meta' );
				$offset = $this->get_records( $run_id, $offset );
			} while ( ! empty( $offset ) && $this->get_run_id() );

			$this->update_run_option( $run_id, 'state', self::STATE_PROCESSING_RECORDS );

			return true;
		} catch ( Exception $e ) {
			$this->log( $e->getMessage() );
			$this->end_run( 'error', $e->getMessage() );
			return new WP_Error( 'air-wp-sync-pro-plus-run-error', $e->getMessage() );
		}
	}

	/**
	 * Log message to file and WPCLI output
	 *
	 * @param string|\WP_Error|\Throwable $message Message to log.
	 * @param string                      $level Log level ("log", "error", "warning").
	 * @param string|false                $run_id Run id.
	 */
	public function log( $message, $level = 'log', $run_id = false ) {
		if ( ! $run_id ) {
			$run_id = $this->get_run_id();
		}
		$this->logger->log( $message, $this->infos()->get( 'slug' ) . '-' . gmdate( 'Y-m-d' ) . '-' . $run_id, $level );
	}

	/**
	 * Reset errors.
	 *
	 * @return void
	 */
	public function reset_errors() {
		delete_post_meta( $this->infos()->get( 'id' ), 'errors' );
	}

	/**
	 * Add an error (string or WP_Error object ) to the importer error list.
	 *
	 * @param string|WP_Error $error Error to add to the importer error list.
	 *
	 * @return void
	 */
	public function add_error( $error ) {
		$post_id = $this->infos()->get( 'id' );

		$errors = get_post_meta( $post_id, 'errors', true );

		if ( ! is_array( $errors ) ) {
			$errors = array();
		}

		$errors[] = $error;

		update_post_meta( $post_id, 'errors', $errors );
	}

	/**
	 * Return errors.
	 *
	 * @return array
	 */
	public function get_errors() {
		$importer_id = $this->infos()->get( 'id' );
		$errors      = get_post_meta( $importer_id, 'errors', true );
		if ( ! is_array( $errors ) ) {
			$errors = array();
		}
		// Backward compatibility.
		$last_error = get_post_meta( $importer_id, 'last_error', true );
		if ( $last_error ) {
			array_unshift( $errors, $last_error );
		}
		return $errors;
	}

	/**
	 * Process a record import.
	 * Return:
	 * - on success an array with the content id created on WordPress as first element and null as second element.
	 * - on failure  an array with null as first element and a WP_Error object as second element.
	 *
	 * @param object $record Remote record.
	 *
	 * @return array
	 */
	public function process_remote_record( $record ) {
		$this->log( sprintf( 'Record ID %s', $record->id ) );
		try {
			$is_delete_strategy = 'delete' === $this->config()->get( 'sync_strategy' );
			// Check if we have existing content for this record.
			$content_id           = $this->get_existing_content_id( $record, ! $is_delete_strategy );
			$should_import_record = ! $is_delete_strategy;
			if ( $content_id ) {
				$this->log( sprintf( '- Found matching content, ID %s', $content_id ) );
				// Check if we should skip updating it.
				if ( 'add' === $this->config()->get( 'sync_strategy' ) || ! $this->needs_update( $content_id, $record ) ) {
					$should_import_record = false;
					$this->log( sprintf( '- No update needed' ) );
				} elseif ( $is_delete_strategy ) {
					if ( $this->delete_created_content( $content_id ) ) {
						$this->log( sprintf( '- Content deleted, ID %s', $content_id ) );
					} else {
						$this->log( sprintf( '- Could not delete content, ID %s', $content_id ), 'error' );
					}
				}
			} else {
				$content_id = null;
			}
			if ( $should_import_record ) {
				$this->log( sprintf( $content_id ? '- Update record %s' : '- Create record %s', $record->id ) );

				$record = apply_filters( 'airwpsync/pre_import_record_data', $record, $this );
				$record = apply_filters( 'airwpsync/import_record_data', $record, $this );
				$fields = $this->get_mapped_fields( $record );

				$content_id = $this->import_record( $record, $fields, $content_id );

				if ( has_filter( 'airwpsync/importer_post_process_jobs' ) ) {
					$post_process_jobs = $this->get_run_option( $this->get_run_id(), 'post-process-jobs', array() );
					/**
					 * Filters post import process jobs.
					 *
					 * @param array $post_process_jobs A list of jobs to process. For each item in the list: one job key and an array of parameters.
					 * @param Abstract_Importer $importer The importer.
					 * @param array $fields Fields.
					 * @param \stdClass $record Remote record.
					 * @param mixed $existing_object_id WordPress object id.
					 */
					$new_post_process_jobs = apply_filters( 'airwpsync/importer_post_process_jobs', array(), $this, $fields, $record, $content_id );
					// Prepend the record id and the content id to the job.
					$new_post_process_jobs = array_map(
						function ( $post_process_job ) use ( $record, $content_id ) {
							array_unshift( $post_process_job, $record->id, $content_id );
							return $post_process_job;
						},
						$new_post_process_jobs
					);
					$this->update_run_option( $this->get_run_id(), 'post-process-jobs', array_merge( $post_process_jobs, $new_post_process_jobs ) );

					// Save record and fields for post processing.
					$this->update_run_option( $this->get_run_id(), 'post-process-jobs-' . $record->id, array( $record, $fields ) );
				}
			}

			return array( $content_id, null );
		} catch ( Exception $e ) {
			$this->log( $e );
			return array( false, new WP_Error( 'air-wp-sync-pro-plus-run-error', $e->getMessage() ) );
		}
	}

	/**
	 * Return importer's module.
	 *
	 * @return Abstract_Module
	 */
	public function get_module() {
		return $this->module;
	}

	/**
	 * Set run ASAP.
	 * If true after the current import has completed a new import will start.
	 *
	 * @param bool $run_asap Run ASAP: true/false.
	 *
	 * @return void
	 */
	public function set_run_asap( $run_asap ) {
		$this->config()->set( 'run_asap', $run_asap );
		$post_id = $this->infos()->get( 'id' );
		update_post_meta( $post_id, 'airwpsync_config', wp_json_encode( $this->config() ) );
	}

	/**
	 * Delete other content existing in WP but deleted in AT.
	 *
	 * @param string $run_id Run id.
	 */
	abstract public function delete_removed_contents( $run_id );

	/**
	 * End run
	 *
	 * @param string      $status Import status ("success", "cancel" or "error").
	 * @param string|null $error Error message.
	 */
	public function end_run( $status = 'success', $error = null ) {
		global $wpdb;
		$importer_id    = $this->infos()->get( 'id' );
		$run_id         = $this->get_run_id();
		$latest_log_url = $this->get_latest_log_file_url();

		$count_deleted = 0;
		if ( 'add_update_delete' === $this->config()->get( 'sync_strategy' ) && 'success' === $status ) {
			$count_deleted = $this->delete_removed_contents( $run_id );
		}
		$processed_content_id = $this->get_run_option( $run_id, 'content_ids', array() );
		$count_processed      = is_array( $processed_content_id ) ? count( $processed_content_id ) : 0;
		$start_date_string    = get_option( 'airwpsync-' . $importer_id . '-run-' . $run_id . '-start-date' );

		// Delete any remaining AS actions.
		$as_deletion_args = array(
			'importer_id' => $importer_id,
			'run_id'      => $run_id,
		);
		Helper::delete_as_actions( 'airwpsync_process_records', \ActionScheduler_Store::STATUS_PENDING, $as_deletion_args );
		Helper::delete_as_actions( 'airwpsync_importer_post_process', \ActionScheduler_Store::STATUS_PENDING, $as_deletion_args );
		Helper::delete_as_actions( 'airwpsync_post_process_job', \ActionScheduler_Store::STATUS_PENDING, $as_deletion_args );

		// Delete temporary options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( sprintf( 'airwpsync-%s-run-%s-', $importer_id, $run_id ) ) . '%'
			)
		);

		$action_ids = \ActionScheduler::store()->query_actions(
			array(
				'hook'                  => 'airwpsync_process_records',
				'status'                => \ActionScheduler_Store::STATUS_FAILED,
				'partial_args_matching' => 'like',
				'args'                  => array(
					'importer_id' => $importer_id,
					'run_id'      => $run_id,
				),
				'per_page'              => -1,
			)
		);
		if ( count( $action_ids ) > 0 ) {
			$this->add_error(
				sprintf(
				// translators: %1$s: start link tag, %2$s end link tag.
					__( 'Some tasks have been interrupted, %1$smore details%2$s', 'air-wp-sync-pro-plus' ),
					'<a href="' . esc_url( admin_url( 'tools.php?page=action-scheduler&status=failed&s=' . $run_id . '&action=-1&action2=-1&paged=1' ) ) . '">',
					'</a>'
				)
			);
		}
		// Remove temporary metas.
		update_post_meta( $importer_id, 'run', '' );
		update_post_meta( $importer_id, 'table_fields', '' );
		$importer_errors = $this->get_errors();
		if ( $importer_errors && 'success' === $status ) {
			$status = 'error';
		}
		// Update status and error.
		update_post_meta( $importer_id, 'status', $status );
		update_post_meta( $importer_id, 'last_error', is_null( $error ) ? '' : $error );
		update_post_meta( $importer_id, 'latest_log_url', $latest_log_url );

		// Save date if success.
		if ( 'success' === $status ) {
			update_post_meta( $importer_id, 'last_updated', gmdate( 'Y-m-d H:i:s' ) );
			update_post_meta( $importer_id, 'count_processed', $count_processed );
			update_post_meta( $importer_id, 'count_deleted', $count_deleted );

			if ( $start_date_string ) {
				update_post_meta( $importer_id, 'last_start_date', $start_date_string );

				$start_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $start_date_string );
				$sync_time      = date_diff( new DateTime( 'now' ), $start_datetime, true );
				update_post_meta( $importer_id, 'last_sync_time', $sync_time->format( '%s' ) );
			}
		}

		$this->log( 'Import ends', 'log', $run_id );

		/**
		 * Fires once the sync is completed (whether it’s a success or not).
		 *
		 * @param Abstract_Importer $importer The importer instance.
		 * @param string $status The status of the sync ("success", "error", "cancel").
		 * @param string|null $error The error message if any.
		 */
		do_action( 'airwpsync/import_completed', $this, $status, $error );

		if ( $this->config()->get( 'run_asap' ) ) {
			$this->set_run_asap( false );
			wp_schedule_single_event( time(), $this->get_schedule_slug() . '-once' );
		}
	}

	/**
	 * Get table fields from API.
	 */
	public function get_table_fields() {
		return $this->remote_data_provider->get_table_fields();
	}

	/**
	 * Get remote records from API.
	 *
	 * @param string $run_id Import run id.
	 * @param int    $offset Pagination offset.
	 *
	 * @return false
	 */
	protected function get_records( $run_id, $offset = 0 ) {
		$records_result = $this->remote_data_provider->get_records( $offset );
		// Loop through all records.
		$chunks = array_chunk( $records_result->records, 10 );
		foreach ( $chunks as $chunk ) {
			// Save Remote record as a temporary option.
			$item_id = uniqid( 'airwpsync-' . $this->infos()->get( 'id' ) . '-run-' . $this->get_run_id() . '-item-' );
			update_option( $item_id, $chunk );
			// Add it to queue.
			as_enqueue_async_action(
				'airwpsync_process_records',
				array(
					'importer_id' => $this->infos()->get( 'id' ),
					'run_id'      => $run_id,
					'item_id'     => $item_id,
				)
			);
		}

		return $records_result->next_offset;
	}

	/**
	 * Get mapped fields
	 *
	 * @param object $record Remote record.
	 */
	protected function get_mapped_fields( $record ) {
		// Make sure we have a value for each mapping.
		$mapping     = ! empty( $this->config()->get( 'mapping' ) ) ? $this->config()->get( 'mapping' ) : array();
		$remote_keys = array_map(
			function ( $mapping_pair ) {
				if ( preg_match( '/(.+)\.(.+)/', $mapping_pair['airtable'], $matches ) ) {
					$remote_id = $matches[1];
				} else {
					$remote_id = $mapping_pair['airtable'];
				}
				return $remote_id;
			},
			$mapping
		);

		$fields = array();
		foreach ( $remote_keys as $remote_key ) {
			$fields[ $remote_key ] = $record->fields->$remote_key ?? '';
		}

		return apply_filters( 'airwpsync/import_record_fields', $fields, $this );
	}

	/**
	 * Load importer settings from post object
	 *
	 * @param \WP_Post $importer_post_object Post object holding the importer config.
	 */
	protected function load_settings( $importer_post_object ) {
		$infos = array(
			'id'       => $importer_post_object->ID,
			'slug'     => $importer_post_object->post_name,
			'title'    => $importer_post_object->post_title,
			'modified' => $importer_post_object->post_modified_gmt,
			'hash'     => wp_hash( $importer_post_object->ID ),
		);

		$this->infos = new Importer_Settings( $infos );

		$json_config = get_post_meta( $importer_post_object->ID, 'airwpsync_config', true );
		if ( $json_config ) {
			$config = json_decode( $json_config, true );
		} else {
			$config = json_decode( $importer_post_object->post_content, true );
		}
		$this->config = new Importer_Settings( $config );
	}

	/**
	 * Get cron schedule slug.
	 *
	 * @return string
	 */
	protected function get_schedule_slug() {
		return 'air_wp_sync_importer_' . $this->infos()->get( 'id' );
	}

	/**
	 * Init cron events
	 */
	protected function schedule_cron_event() {
		if ( 'cron' === $this->config()->get( 'scheduled_sync.type' ) && $this->config()->get( 'scheduled_sync.recurrence' ) ) {
			add_action( $this->get_schedule_slug(), array( $this, 'cron' ) );
			if ( false === $this->get_next_scheduled_sync() ) {
				wp_schedule_event( $this->get_schedule_timestamp(), $this->config()->get( 'scheduled_sync.recurrence' ), $this->get_schedule_slug() );
			}
		} elseif ( $this->get_next_scheduled_sync() ) {
			wp_clear_scheduled_hook( $this->get_schedule_slug() );
		}

		if ( 'after_connection_synced' === $this->config()->get( 'scheduled_sync.type' ) ) {
			$after_connection_synced = (int) $this->config()->get( 'scheduled_sync.after_connection_synced' );
			$importer_schedule_slug  = $this->get_schedule_slug();
			add_action(
				'airwpsync/import_completed',
				function ( $importer, $status ) use ( $after_connection_synced, $importer_schedule_slug ) {
					if ( 'success' !== $status || $importer->infos()->get( 'id' ) !== $after_connection_synced ) {
						return;
					}
					wp_schedule_single_event( time(), $importer_schedule_slug . '-once' );
				},
				10,
				2
			);
		}

		add_action( $this->get_schedule_slug() . '-once', array( $this, 'cron' ) );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'check_import_state' ) );
	}

	/**
	 * Get Schedule timestamp
	 */
	protected function get_schedule_timestamp() {
		$datetime   = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
		$recurrence = $this->config()->get( 'scheduled_sync.recurrence' );
		if ( 'weekly' === $recurrence ) {
			if ( $this->config()->get( 'scheduled_sync.weekday' ) ) {
				$datetime->modify( 'next ' . $this->config()->get( 'scheduled_sync.weekday' ) );
			}
		}
		if ( in_array( $recurrence, array( 'weekly', 'daily' ), true ) ) {
			if ( $this->config()->get( 'scheduled_sync.time' ) ) {
				$time = explode( ':', $this->config()->get( 'scheduled_sync.time' ) );
				$datetime->setTime( $time[0], $time[1] );
			}
		} else {
			$schedules = wp_get_schedules();
			$interval  = isset( $schedules[ $recurrence ] ) ? $schedules[ $recurrence ]['interval'] : HOUR_IN_SECONDS;
			$datetime->add( new DateInterval( 'PT' . $interval . 'S' ) );
		}
		return $datetime->getTimestamp();
	}

	/**
	 * Update import state based on the action scheduler tasks state.
	 * 1. STATE_INIT
	 * 2. STATE_PROCESSING_RECORDS: process records saved in `get_records`
	 * 3. STATE_PROCESSING_POST_JOBS: process post jobs saved during the import if any.
	 * 4. STATE_COMPLETED: once the import is completed.
	 *
	 * @return void
	 */
	public function check_import_state() {
		// Not needed if there is no import running.
		if ( ! $this->get_run_id() ) {
			return;
		}
		$importer_id          = $this->infos()->get( 'id' );
		$run_id               = $this->get_run_id();
		$as_actions_args      = array(
			'importer_id' => $importer_id,
			'run_id'      => $run_id,
		);
		$current_import_state = $this->get_run_option( $this->get_run_id(), 'state' );
		if ( ! $current_import_state ) {
			// Let’s assume it's an edge case like migration from old progress check mechanism to new one.
			$current_import_state = self::STATE_PROCESSING_POST_JOBS;
		}
		$pending_running_statuses = array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING );

		$is_processing_record         = count( Helper::get_as_actions( 'airwpsync_process_records', $pending_running_statuses, $as_actions_args ) ) > 0;
		$is_running_post_process_jobs = count( Helper::get_as_actions( 'airwpsync_post_process_job', $pending_running_statuses, $as_actions_args ) ) > 0;

		if ( self::STATE_PROCESSING_RECORDS === $current_import_state && ! $is_processing_record ) {
			$post_process_jobs = $this->get_run_option( $run_id, 'post-process-jobs', array() );
			if ( count( $post_process_jobs ) > 0 ) {
				$chunks = array_chunk( $post_process_jobs, 100 );
				foreach ( $chunks as $chunk_index => $chunk ) {
					// Add it to queue. We will find jobs back later with the "post-process-jobs" run option.
					// We can't pass jobs directly as param or Helper::get_as_actions function won't be able to find actions...
					$action_id = as_enqueue_async_action(
						'airwpsync_post_process_job',
						array(
							'importer_id'      => $importer_id,
							'run_id'           => $run_id,
							'jobs_chunk_index' => $chunk_index,
						)
					);
				}
				$this->log( sprintf( '%d post processing job(s) added', count( $post_process_jobs ) ) );

				$this->update_run_option( $run_id, 'state', self::STATE_PROCESSING_POST_JOBS );
			} else {
				$this->log( 'No post processing jobs' );
				$this->update_run_option( $run_id, 'state', self::STATE_COMPLETED );
			}
		} elseif ( self::STATE_PROCESSING_POST_JOBS === $current_import_state && ! $is_running_post_process_jobs ) {
			$this->log( 'Post processing jobs completed' );
			$this->update_run_option( $run_id, 'state', self::STATE_COMPLETED );
		}

		$new_state = $this->get_run_option( $run_id, 'state' );
		if ( self::STATE_COMPLETED === $new_state ) {
			// All jobs done, end import.
			$this->end_run();
		}
	}

	/**
	 * Append new content_id to the run result
	 *
	 * @param mixed  $content_id WordPress object id.
	 * @param string $run_id Run id.
	 */
	public function append_run_result( $content_id, $run_id ) {
		$content_ids = $this->get_run_option( $run_id, 'content_ids', array() );
		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}
		$content_ids[] = $content_id;
		$this->update_run_option( $run_id, 'content_ids', $content_ids );
	}

	/**
	 * Compare hashes to check if WP object needs update
	 *
	 * @param mixed  $content_id WordPress object id.
	 * @param object $record Remote record.
	 *
	 * @return bool
	 */
	protected function needs_update( $content_id, $record ) {
		if ( apply_filters( 'airwpsync/importer_force_update', false, $content_id, $record ) ) {
			return true;
		}
		return $this->generate_hash( $record ) !== $this->get_existing_content_hash( $content_id );
	}

	/**
	 * Generate hash for given remote record
	 *
	 * @param object $record Remote record.
	 *
	 * @return string
	 */
	protected function generate_hash( $record ) {
		return Helper::generate_hash( $record, $this->config()->to_array() );
	}

	/**
	 * Returns the latest log file URL if any.
	 *
	 * @return string|false
	 */
	public function get_latest_log_file_url() {
		$log_files = $this->logger->get_log_files();
		if ( count( $log_files ) === 0 ) {
			return false;
		}
		$latest_log_file = array_shift( $log_files );
		$log_dir_url     = $this->logger->get_log_dir_url();

		return str_replace( $this->logger->get_log_dir(), $log_dir_url, $latest_log_file );
	}

	/**
	 * Init
	 */
	protected function init() {
	}

	/**
	 * Returns the remote data provider.
	 *
	 * @return Importer_Remote_Data_Provider
	 */
	public function get_remote_data_provider(): Importer_Remote_Data_Provider {
		return $this->remote_data_provider;
	}

	/**
	 * Import remote record and return the WordPress object id created / updated.
	 *
	 * @param object     $record Remote record.
	 * @param array      $fields Remote record fields.
	 * @param mixed|null $existing_object_id WordPress object id.
	 *
	 * @return mixed
	 */
	abstract protected function import_record( $record, $fields, $existing_object_id = null );

	/**
	 * Get existing content id
	 *
	 * @param object $record Remote record.
	 * @param bool   $exclude_other_connections Exclude other connections from the search.
	 *
	 * @return mixed|false
	 */
	abstract protected function get_existing_content_id( $record, $exclude_other_connections = true );

	/**
	 * Get existing content hash
	 *
	 * @param mixed $content_id WordPress object id.
	 */
	abstract protected function get_existing_content_hash( $content_id );

	/**
	 * Delete previously created content.
	 *
	 * @param mixed $object_id WordPress object id.
	 * @return bool true if the content has been deleted.
	 */
	abstract public function delete_created_content( $object_id );
}
