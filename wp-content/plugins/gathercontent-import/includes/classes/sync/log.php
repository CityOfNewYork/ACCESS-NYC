<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Base as Plugin_Base;
use WP_Error;

/**
 * Base class for logging pushing/pulling errors.
 *
 * @since 3.0.0
 */
class Log extends Plugin_Base {

	/**
	 * GatherContent\Importer\Sync\Base
	 *
	 * @since  3.0.0
	 *
	 * @var GatherContent\Importer\Sync\Base object
	 */
	protected $sync = null;

	/**
	 * WP_Error
	 *
	 * @since  3.0.0
	 *
	 * @var WP_Error object
	 */
	protected $error = null;

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'gc_sync_items_result', array( $this, 'handle_logging' ), 10, 2 );
	}

	/**
	 * Handles logging sync errors to mapping post-meta.
	 *
	 * @since  3.0.0
	 *
	 * @param  mixed $maybe_error Result of sync. WP_Error on failure.
	 * @param  Base  $sync        GatherContent\Importer\Sync\Base object.
	 *
	 * @return void
	 */
	public function handle_logging( $maybe_error, $sync ) {
		if (
			! is_wp_error( $maybe_error )
			|| ! $sync->mapping
			|| "gc_{$sync->direction}_item_in_progress" === $maybe_error->get_error_code()
		) {
			// Nothing to do here.
			return;
		}

		$this->sync = $sync;
		$this->error = $maybe_error;
		$this->log_errors();
	}

	/**
	 * Log the error to the mapping post meta.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function log_errors() {
		if ( 0 === strpos( $this->error->get_error_code(), "gc_{$this->sync->direction}_item_fail_" ) ) {

			$data        = $this->error->get_error_data();
			$item_errors = $this->sync->mapping->get_meta( 'item_errors' );
			$item_errors = is_array( $item_errors ) ? $item_errors : array();

			if (
				isset( $item_errors[ $data['sync_item_id'] ] )
				&& $this->error_same( $item_errors[ $data['sync_item_id'] ] )
			) {
				return;
			}

			$item_errors[ $data['sync_item_id'] ] = $this->error;
			$this->sync->mapping->update_meta( 'item_errors', $item_errors );

		} else {
			$last = $this->sync->mapping->get_meta( 'last_error' );

			if ( $this->error_same( $last ) ) {
				// Don't resave existing error.
				return;
			}

			$this->sync->mapping->update_meta( 'last_error', $this->error );
		}
	}

	/**
	 * Checks if the given WP_Error object matches our existing error object.
	 *
	 * @since  3.0.0
	 *
	 * @param  WP_Error $to_compare WP_Error object.
	 *
	 * @return bool Whether given WP_Error object matches existing error object.
	 */
	public function error_same( $to_compare ) {
		return (
			$to_compare && is_wp_error( $to_compare )
			&& $to_compare->get_error_code() === $this->error->get_error_code()
			&& $to_compare->get_error_message() === $this->error->get_error_message()
		);
	}

}
