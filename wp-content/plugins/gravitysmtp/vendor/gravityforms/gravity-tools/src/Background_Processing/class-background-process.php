<?php

namespace Gravity_Forms\Gravity_Tools\Background_Processing;

use Gravity_Forms\Gravity_Tools\Cache\Cache;
use Gravity_Forms\Gravity_Tools\Logging\Logger;
use Gravity_Forms\Gravity_Tools\Utils\Common;

/**
 * Abstract Background_Process class.
 *
 * @since 1.0
 *
 * @abstract
 * @extends WP_Async_Request
 */
abstract class Background_Process extends WP_Async_Request {

	/**
	 * Action
	 *
	 * @since  1.0
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_process';

	/**
	 * Start time of current process.
	 *
	 * @since  1.0
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;

	/**
	 * Cron_hook_identifier
	 *
	 * @since  1.0
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_hook_identifier;

	/**
	 * Cron_interval_identifier
	 *
	 * @since  1.0
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_interval_identifier;

	/**
	 * Query_url
	 *
	 * @since  2.3
	 *
	 * @var string
	 * @access protected
	 */
	protected $query_url;

	/**
	 * @var Common
	 */
	protected $utils;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var Cache
	 */
	protected $cache;

	/**
	 * Initiate new background process
	 *
	 * @since 1.0
	 */
	public function __construct( Common $utils, Logger $logger, Cache $cache ) {
		parent::__construct();

		$this->utils  = $utils;
		$this->logger = $logger;
		$this->cache  = $cache;

		$this->query_url                = admin_url( 'admin-ajax.php' );
		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
	}

	/**
	 * Dispatches the queued tasks to Admin Ajax for processing and schedules a cron job in case processing fails.
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @return array|WP_Error
	 */
	public function dispatch() {
		// @todo implement logging
		$this->logger->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );

		if ( $this->is_queue_empty() ) {
			$this->clear_scheduled_event();

			$dispatched = new \WP_Error( 'queue_empty', 'Nothing left to process' );
		} else {
			// Schedule the cron healthcheck.
			$this->schedule_event();

			// Perform remote post.
			$dispatched = parent::dispatch();
		}

		if ( is_wp_error( $dispatched ) ) {
			$this->logger->log_debug( sprintf( '%s(): Unable to dispatch tasks to Admin Ajax: %s', __METHOD__, $dispatched->get_error_message() ) );
		}

		return $dispatched;
	}

	/**
	 * Get the dispatch request arguments.
	 *
	 * @since 2.3-rc-2
	 *
	 * @return array
	 */
	protected function get_post_args() {
		$post_args = parent::get_post_args();

		// Blocking prevents some issues such as cURL connection errors being reported.
		unset( $post_args['blocking'] );

		return $post_args;
	}

	/**
	 * Push to queue
	 *
	 * @since 1.0
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		$this->data[] = $data;

		return $this;
	}

	/**
	 * Save queue
	 *
	 * @since 1.0
	 *
	 * @return $this
	 */
	public function save() {
		$key = $this->generate_key();

		if ( ! empty( $this->data ) ) {
			$data = array(
				'blog_id' => get_current_blog_id(),
				'data'    => $this->data,
			);
			update_site_option( $key, $data );
		}

		return $this;
	}

	/**
	 * Update queue
	 *
	 * @since 1.0
	 *
	 * @param string $key  Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $data ) {
		if ( ! empty( $data ) ) {
			$old_value = get_site_option( $key );
			if ( $old_value ) {
				$this->logger->log_debug( sprintf( '%s(): Updating batch %s. Tasks remaining: %d.', __METHOD__, $key, count( $data ) ) );
				$data = array(
					'blog_id' => get_current_blog_id(),
					'data'    => $data,
				);
				update_site_option( $key, $data );
			}
		}

		return $this;
	}

	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		$this->logger->log_debug( sprintf( '%s(): Deleting batch %s.', __METHOD__, $key ) );
		delete_site_option( $key );

		return $this;
	}

	/**
	 * Generate key
	 *
	 * Generates a unique key based on microtime. Queue items are
	 * given a unique key so that they can be merged upon save.
	 *
	 * @since 1.0
	 *
	 * @param int $length Length.
	 *
	 * @return string
	 */
	protected function generate_key( $length = 64 ) {
		$unique  = md5( microtime() . rand() );
		$prepend = $this->identifier . '_batch_blog_id_' . get_current_blog_id() . '_';

		return substr( $prepend . $unique, 0, $length );
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 *
	 * @since 1.0
	 */
	public function maybe_handle() {
		$this->logger->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );

		// Don't lock up other requests while processing
		session_write_close();

		if ( $this->is_process_running() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
	}

	/**
	 * Is queue empty
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		return ( $count > 0 ) ? false : true;
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 *
	 * @since 1.0
	 */
	protected function is_process_running() {
		$running        = false;
		$lock_timestamp = get_site_option( $this->identifier . '_process_lock' );
		if ( $lock_timestamp ) {

			$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
			$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

			if ( microtime( true ) - $lock_timestamp > $lock_duration ) {
				$this->unlock_process();
			} else {
				$running = true;
			}
		}

		return $running;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 *
	 * @since 1.0
	 */
	protected function lock_process() {
		$this->start_time = time(); // Set start time of current process.

		update_site_option( $this->identifier . '_process_lock', microtime( true ) );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @since 1.0
	 *
	 * @return $this
	 */
	public function unlock_process() {
		delete_site_option( $this->identifier . '_process_lock' );

		return $this;
	}

	/**
	 * Get batch
	 *
	 * @since 1.0
	 *
	 * @return stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		global $wpdb;

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_blog_id_' . get_current_blog_id() . '_' ) . '%';

		$sql = "
					SELECT *
					FROM {$table}
					WHERE {$column} LIKE %s
					ORDER BY {$key_column} ASC
					LIMIT 1
				";

		$query = $wpdb->get_row( $wpdb->prepare( $sql, $key ) );

		if ( empty( $query ) ) {
			// No more batches for this blog ID. Get the next one in the queue regardless of the blog ID.
			$key   = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
			$query = $wpdb->get_row( $wpdb->prepare( $sql, $key ) );
		}

		$batch          = new \stdClass();
		$batch->key     = $query->$column;
		$value          = maybe_unserialize( $query->$value_column );
		$batch->data    = $value['data'];
		$batch->blog_id = $value['blog_id'];

		return $batch;
	}

	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 *
	 * @since 1.0
	 */
	protected function handle() {
		$this->lock_process();

		do {
			$batch = $this->get_batch();

			if ( is_multisite() ) {
				$current_blog_id = get_current_blog_id();
				if ( $current_blog_id !== $batch->blog_id ) {
					$this->spawn_multisite_child_process( $batch->blog_id );
					if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
						// Switch back to the current blog and return so the other tasks queued in this process can be run.
						switch_to_blog( $current_blog_id );

						return;
					} else {
						wp_die();
					}
				}
			}

			$this->logger->log_debug( sprintf( '%s(): Processing batch %s; Tasks: %d.', __METHOD__, $batch->key, count( $batch->data ) ) );

			$task_num = 0;

			foreach ( $batch->data as $key => $value ) {
				$this->logger->log_debug( sprintf( '%s(): Processing task %d.', __METHOD__, ++ $task_num ) );
				$task = $this->task( $value );

				if ( $task !== false ) {
					$this->logger->log_debug( sprintf( '%s(): Keeping task %d in batch.', __METHOD__, $task_num ) );
					$batch->data[ $key ] = $task;
				} else {
					$this->logger->log_debug( sprintf( '%s(): Removing task %d from batch.', __METHOD__, $task_num ) );
					unset( $batch->data[ $key ] );
				}

				if ( $task !== false || $this->time_exceeded() || $this->memory_exceeded() ) {
					// Batch limits reached or task not complete.
					break;
				}
			}

			// Update or delete current batch.
			if ( ! empty( $batch->data ) ) {
				$this->update( $batch->key, $batch->data );
			} else {
				$this->delete( $batch->key );
			}
		} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

		$this->logger->log_debug( sprintf( '%s(): Batch completed for %s.', __METHOD__, $this->action ) );

		$this->unlock_process();

		// Start next batch or complete process.
		if ( ! $this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			// Return so the other tasks queued in this process can be run.
			return;
		} else {
			wp_die();
		}
	}

	/**
	 * Spawn a new background process on the multisite that scheduled the current task
	 *
	 * @param int $blog_id
	 *
	 * @since 2.3
	 */
	protected function spawn_multisite_child_process( $blog_id ) {
		$this->logger->log_debug( sprintf( '%s(): Running for blog #%s.', __METHOD__, $blog_id ) );
		switch_to_blog( $blog_id );
		$this->query_url = admin_url( 'admin-ajax.php' );
		$this->unlock_process();
		$this->dispatch();
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || - 1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return $this->convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
	 *
	 * @return int An integer byte value.
	 */
	protected function convert_hr_to_bytes( $value ) {

		// Globally available in WordPress 4.6
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return wp_convert_hr_to_bytes( $value );
		}

		// Backwards compatible support for Wordpress 3.6 to 4.5
		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {
			$bytes *= pow( 1024, 3 );
		} elseif ( false !== strpos( $value, 'm' ) ) {
			$bytes *= pow( 1024, 2 );
		} elseif ( false !== strpos( $value, 'k' ) ) {
			$bytes *= 1024;
		}

		// Deal with large (float) values which run into the maximum integer size.
		return min( $bytes, PHP_INT_MAX );
	}

	/**
	 * Time exceeded.
	 *
	 * @since 1.0
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_time_exceeded', $return );
	}

	/**
	 * Complete.
	 *
	 * @since 1.0
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		// Unschedule the cron healthcheck.
		$this->clear_scheduled_event();
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @since  1.0
	 *
	 * @access public
	 *
	 * @param mixed $schedules Schedules.
	 *
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		$interval = apply_filters( $this->identifier . '_cron_interval', 5 );

		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = apply_filters( $this->identifier . '_cron_interval', $this->cron_interval_identifier );
		}

		// Adds every 5 minutes to the existing schedules.
		$schedules[ $this->identifier . '_cron_interval' ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			'display'  => sprintf( __( 'Every %d Minutes', 'gravityforms' ), $interval ),
		);

		return $schedules;
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 *
	 * @since 1.0
	 */
	public function handle_cron_healthcheck() {
		$this->logger->log_debug( sprintf( '%s(): Running for %s.', __METHOD__, $this->action ) );
		$this->record_cron_event( $this->cron_hook_identifier );

		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}


		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();

			return;
		}

		$this->handle();
	}

	/**
	 * Maintains a semipersistent record of the 3 most recent events for the specified hook.
	 *
	 * @since 2.7.1
	 *
	 * @param string $hook The cron hook name.
	 */
	public function record_cron_event( $hook ) {
		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			return;
		}

		$events = $this->cache->get( Cache::KEY_CRON_EVENTS );
		if ( ! is_array( $events ) ) {
			$events = array();
		}

		if ( empty( $events[ $hook ] ) || ! is_array( $events[ $hook ] ) ) {
			$events[ $hook ] = array( time() );
		} else {
			array_unshift( $events[ $hook ], time() );
			array_splice( $events[ $hook ], 3 );
		}

		$this->cache->set( Cache::KEY_CRON_EVENTS, $events, true );
	}

	/**
	 * Schedule event
	 *
	 * @since 1.0
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			$this->logger->log_debug( sprintf( '%s(): Scheduling cron event for %s.', __METHOD__, $this->action ) );
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Clear scheduled event
	 *
	 * @since 1.0
	 */
	protected function clear_scheduled_event() {
		$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

		if ( $timestamp ) {
			$this->logger->log_debug( sprintf( '%s(): Clearing cron event for %s.', __METHOD__, $this->action ) );
			wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
		}
	}

	/**
	 * Clears all scheduled events.
	 *
	 * @since 2.3.1.x
	 */
	public function clear_scheduled_events() {
		wp_clear_scheduled_hook( $this->cron_hook_identifier );
	}

	/**
	 * Cancel Process
	 *
	 * Stop processing queue items, clear cronjob and delete batch.
	 *
	 * @since 1.0
	 */
	public function cancel_process() {
		if ( ! $this->is_queue_empty() ) {
			$batch = $this->get_batch();

			$this->delete( $batch->key );
			$this->clear_scheduled_events();
		}

	}

	/**
	 * Clears all batches from the queue.
	 *
	 * @since 2.3
	 *
	 * @param bool $all_blogs_in_network
	 *
	 * @return false|int
	 */
	public function clear_queue( $all_blogs_in_network = false ) {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->identifier . '_batch_';

		if ( ! $all_blogs_in_network ) {
			$key .= 'blog_id_' . get_current_blog_id() . '_';
		}

		$key = $wpdb->esc_like( $key ) . '%';

		$result = $wpdb->query( $wpdb->prepare( "
			DELETE FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		$this->data = array();

		return $result;
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 1.0
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	abstract protected function task( $item );

}