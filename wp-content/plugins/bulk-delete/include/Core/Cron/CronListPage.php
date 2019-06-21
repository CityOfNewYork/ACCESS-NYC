<?php

namespace BulkWP\BulkDelete\Core\Cron;

use BulkWP\BulkDelete\Core\Base\BasePage;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Page that lists bulk delete cron jobs.
 *
 * @since 6.0.0
 */
class CronListPage extends BasePage {
	public function register() {
		parent::register();

		add_action( 'bd_delete_cron', array( $this, 'delete_cron_job' ) );
		add_action( 'bd_run_cron', array( $this, 'run_cron_job' ) );
	}

	protected function initialize() {
		$this->page_slug  = \Bulk_Delete::CRON_PAGE_SLUG;
		$this->capability = 'manage_options';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Schedules', 'bulk-delete' ),
			'menu_title' => __( 'Scheduled Jobs', 'bulk-delete' ),
		);
	}

	protected function render_body() {
		$cron_list_table = new CronListTable( $this->get_cron_schedules() );
		$cron_list_table->prepare_items();
		$cron_list_table->display();

		// TODO: Move this to a seperate Add-on page.
		bd_display_available_addon_list();
	}

	/**
	 * Process run cron job request.
	 *
	 * @since 6.0
	 */
	public function run_cron_job() {
		$cron_id    = absint( $_GET['cron_id'] );
		$cron_items = $this->get_cron_schedules();

		if ( 0 === $cron_id ) {
			return;
		}

		if ( ! isset( $cron_items[ $cron_id ] ) ) {
			return;
		}

		wp_schedule_single_event( time(), $cron_items[ $cron_id ]['type'], $cron_items[ $cron_id ]['args'] );

		$msg = __( 'The selected scheduled job has been successfully started. It will now run in the background.', 'bulk-delete' );

		add_settings_error(
			\Bulk_Delete::CRON_PAGE_SLUG, // TODO: Replace this constant.
			'deleted-cron',
			$msg,
			'updated'
		);
	}

	/**
	 * Process delete cron job request.
	 *
	 * @since 5.0
	 * @since 6.0.0 Moved into CronListPage class
	 */
	public function delete_cron_job() {
		$cron_id    = absint( $_GET['cron_id'] );
		$cron_items = $this->get_cron_schedules();

		if ( 0 === $cron_id ) {
			return;
		}

		if ( ! isset( $cron_items[ $cron_id ] ) ) {
			return;
		}

		wp_unschedule_event( $cron_items[ $cron_id ]['timestamp'], $cron_items[ $cron_id ]['type'], $cron_items[ $cron_id ]['args'] );

		$msg = __( 'The selected scheduled job was successfully deleted ', 'bulk-delete' );

		add_settings_error(
			\Bulk_Delete::CRON_PAGE_SLUG, // TODO: Replace this constant.
			'deleted-cron',
			$msg,
			'updated'
		);
	}

	/**
	 * Get the list of cron schedules.
	 *
	 * @since 6.0.0 Moved into CronListPage class
	 *
	 * @return array The list of cron schedules
	 */
	protected function get_cron_schedules() {
		$cron_items  = array();
		$cron        = _get_cron_array();
		$date_format = _x( 'M j, Y @ G:i', 'Cron table date format', 'bulk-delete' );
		$schedules   = wp_get_schedules();
		$i           = 1;

		foreach ( $cron as $timestamp => $cronhooks ) {
			foreach ( (array) $cronhooks as $hook => $events ) {
				if ( 'do-bulk-delete-' === substr( $hook, 0, 15 ) ) {
					$cron_item = array();

					foreach ( (array) $events as $key => $event ) {
						$cron_item['timestamp'] = $timestamp;
						$cron_item['due']       = date_i18n( $date_format, $timestamp + ( get_option( 'gmt_offset' ) * 60 * 60 ) );
						$cron_item['type']      = $hook;
						$cron_item['args']      = $event['args'];
						$cron_item['id']        = $i;

						if ( isset( $schedules[ $event['schedule'] ] ) ) {
							$cron_item['schedule'] = $schedules[ $event['schedule'] ]['display'];
						} else {
							$cron_item['schedule'] = $event['schedule'];
						}
					}

					$cron_items[ $i ] = $cron_item;
					$i ++;
				}
			}
		}

		return $cron_items;
	}
}
