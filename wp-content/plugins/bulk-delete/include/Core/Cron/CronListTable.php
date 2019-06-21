<?php

namespace BulkWP\BulkDelete\Core\Cron;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Table that lists bulk delete cron jobs.
 *
 * @since 6.0.0 Added namespace.
 */
class CronListTable extends \WP_List_Table {
	/**
	 * Constructor for creating new CronListTable.
	 *
	 * @param array $cron_jobs List of cron jobs.
	 */
	public function __construct( $cron_jobs ) {
		$this->items = $cron_jobs;

		parent::__construct(
			array(
				'singular' => 'cron_list',  // Singular label.
				'plural'   => 'cron_lists', // Plural label, also this well be one of the table css class.
				'ajax'     => false,        // We won't support Ajax for this table.
			)
		);
	}

	/**
	 * Add extra markup in the toolbars before or after the list.
	 *
	 * @param string $which Whether the markup should appear after (bottom) or before (top) the list.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<p>';
			_e( 'This is the list of jobs that are currently scheduled for auto deleting posts in Bulk Delete Plugin.', 'bulk-delete' );
			$total_items = count( $this->items );
			if ( 0 === $total_items ) {
				echo ' <strong>';
				_e( 'Note: ', 'bulk-delete' );
				echo '</strong>';
				_e( 'Scheduling auto post or user deletion is available only when you buy pro addons.', 'bulk-delete' );
			}
			echo '</p>';
		}
	}

	/**
	 * Define the columns that are going to be used in the table.
	 *
	 * @return array Array of columns to use with the table
	 */
	public function get_columns() {
		return array(
			'col_cron_due'      => __( 'Next Due', 'bulk-delete' ),
			'col_cron_schedule' => __( 'Schedule', 'bulk-delete' ),
			'col_cron_type'     => __( 'Type', 'bulk-delete' ),
			'col_cron_options'  => __( 'Options', 'bulk-delete' ),
		);
	}

	/**
	 * Decide which columns to activate the sorting functionality on.
	 *
	 * @return array Array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return array(
			'col_cron_type' => array( 'cron_type', true ),
		);
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements.
	 */
	public function prepare_items() {
		$total_items    = count( $this->items );
		$items_per_page = 50;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $items_per_page ),
			'per_page'    => $items_per_page,
		) );

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	/**
	 * Display cron due date column.
	 *
	 * @param array $item The item to be displayed in the row.
	 *
	 * @return string Column output.
	 */
	public function column_col_cron_due( $item ) {
		$actions = array(
			'delete' => sprintf( '<a href="?page=%s&bd_action=%s&cron_id=%s&%s=%s">%s</a>',
				$_REQUEST['page'],
				'delete_cron',
				$item['id'],
				'bd-delete_cron-nonce',
				wp_create_nonce( 'bd-delete_cron' ),
				__( 'Delete', 'bulk-delete' )
			),
			'run' => sprintf( '<a href="?page=%s&bd_action=%s&cron_id=%s&%s=%s" onclick="return confirm(%s)">%s</a>',
				$_REQUEST['page'],
				'run_cron',
				$item['id'],
				'bd-run_cron-nonce',
				wp_create_nonce( 'bd-run_cron' ),
				__( "'Are you sure you want to run the schedule job manually'", 'bulk-delete' ),
				__( 'Run Now', 'bulk-delete' )
			),
		);

		// Return the title contents.
		return sprintf( '%1$s <span style="color:silver">(%2$s)</span>%3$s',
			/*$1%s*/
			$item['due'],
			/*$2%s*/
			( $item['timestamp'] + get_option( 'gmt_offset' ) * 60 * 60 ),
			/*$3%s*/
			$this->row_actions( $actions )
		);
	}

	/**
	 * Display cron schedule column.
	 *
	 * @param array $item The item to be displayed in the row.
	 */
	public function column_col_cron_schedule( $item ) {
		echo $item['schedule'];
	}

	/**
	 * Display cron type column.
	 *
	 * @param array $item The item to be displayed in the row.
	 */
	public function column_col_cron_type( $item ) {
		if ( isset( $item['args'][0]['cron_label'] ) ) {
			echo esc_html( $item['args'][0]['cron_label'] );
		} else {
			echo esc_html( $item['type'] );
		}
	}

	/**
	 * Display cron options column.
	 *
	 * @param array $item The item to be displayed in the row.
	 */
	public function column_col_cron_options( $item ) {
		// TODO: Make it pretty
		print_r( $item['args'] );
	}

	/**
	 * Generates the message when no items are present.
	 */
	public function no_items() {
		_e( 'You have not scheduled any bulk delete jobs.', 'bulk-delete' );
	}
}
