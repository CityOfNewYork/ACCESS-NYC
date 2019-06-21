<?php

namespace BulkWP\BulkDelete\Core\SystemInfo;

use BulkWP\BulkDelete\Core\BulkDelete;
use Sudar\WPSystemInfo\SystemInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete System Info.
 *
 * Uses the WPSystemInfo library.
 *
 * @since 6.0.0
 * @link  https://github.com/sudar/wp-system-info
 */
class BulkDeleteSystemInfo extends SystemInfo {
	/**
	 * Setup hooks and filters.
	 */
	public function load() {
		add_action( 'before_system_info_for_bulk-delete', array( $this, 'print_bulk_delete_details' ) );
	}

	/**
	 * Print details about Bulk Delete.
	 *
	 * PHPCS is disabled for this function since alignment will mess up the system info output.
	 * phpcs:disable
	 */
	public function print_bulk_delete_details() {
		echo '-- Bulk Delete Configuration --', "\n";
		echo 'Bulk Delete Version:      ', BulkDelete::VERSION, "\n";

		$this->print_license_details();
		$this->print_schedule_jobs();
	}
	// phpcs:enable

	/**
	 * Print License details.
	 */
	protected function print_license_details() {
		$keys = \BD_License::get_licenses();
		if ( ! empty( $keys ) ) {
			echo 'BULKWP-LICENSE:          ', "\n";

			foreach ( $keys as $key ) {
				echo $key['addon-name'];
				echo ' | ';
				echo $key['license'];
				echo ' | ';
				echo $key['expires'];
				echo ' | ';
				echo $key['validity'];
				echo ' | ';
				echo $key['addon-code'];
			}
		}
	}

	/**
	 * Print all schedule jobs.
	 */
	protected function print_schedule_jobs() {
		$cron = _get_cron_array();

		if ( ! empty( $cron ) ) {
			echo "\n", 'SCHEDULED JOBS:          ', "\n";

			$date_format = _x( 'M j, Y @ G:i', 'Cron table date format', 'bulk-delete' );

			foreach ( $cron as $timestamp => $cronhooks ) {
				foreach ( (array) $cronhooks as $hook => $events ) {
					if ( 'do-bulk-delete-' === substr( $hook, 0, 15 ) ) {
						foreach ( (array) $events as $key => $event ) {
							echo date_i18n( $date_format, $timestamp + ( get_option( 'gmt_offset' ) * 60 * 60 ) ) . ' (' . $timestamp . ')';
							echo ' | ';
							echo $event['schedule'];
							echo ' | ';
							echo $hook;
							echo "\n";
						}
					}
				}
			}

			echo "\n";
		}
	}
}
