<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_Tools\Logging\DB_Logging_Provider;
use Gravity_Forms\Gravity_Tools\Logging\Log_Line;

class Debug_Log_Model {

	protected $table_name = 'gravitysmtp_debug_log';

	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	public function create( $line, $priority ) {
		global $wpdb;

		if ( is_numeric( $priority ) ) {
			$priority = $this->map_priority_number_to_string( $priority );
		}

		$wpdb->insert(
			$this->get_table_name(),
			array(
				'line'         => $line,
				'priority'     => $priority,
				'date_created' => current_time( 'mysql', true ),
				'date_updated' => current_time( 'mysql', true ),
			)
		);

		return $wpdb->insert_id;
	}

	private function map_priority_number_to_string( $number ) {
		$map = array(
			DB_Logging_Provider::DEBUG => 'debug',
			DB_Logging_Provider::INFO  => 'info',
			DB_Logging_Provider::WARN  => 'warning',
			DB_Logging_Provider::ERROR => 'error',
			DB_Logging_Provider::FATAL => 'fatal',
		);

		return $map[ $number ];
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s ORDER BY %2$s DESC, %3$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'date_updated', 'id' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function delete_all() {
		$this->clear();
	}

	public function clear() {
		global $wpdb;

		$sql = 'TRUNCATE %1$s;';

		$wpdb->query( $wpdb->prepare( $sql, $this->get_table_name() ), ARRAY_A );
	}

	public function slice( $count, $offset ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql     = "SELECT * FROM $table_name LIMIT %d, %d;";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $count ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function count( $search_term = null, $search_type = null, $priority = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $wpdb->prepare(
				"WHERE `line` LIKE '%%%s%%'",
				$search_term
			);
		}

		$priority_clause = null;

		if ( ! empty( $priority ) ) {
			$priority_clause = $wpdb->prepare( "`priority` = %s", $priority );
			if ( ! empty( $search_term ) ) {
				$priority_clause = ' AND ' . $priority_clause;
			} else {
				$priority_clause = 'WHERE ' . $priority_clause;
			}
		}

		$sql = "SELECT COUNT(1) as 'count' FROM $table_name $search_clause $priority_clause;";
		$results = $wpdb->get_row( $sql, ARRAY_A );

		return $results['count'];
	}

	public function paginate( $page, $per_page, $max_date = false, $search_term = null, $search_type = null, $priority = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$offset = ( $page - 1 ) * $per_page;

		if ( ! $max_date ) {
			$max_date = current_time( 'mysql', true );
		}

		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $wpdb->prepare(
				"`line` LIKE '%%%s%%' AND",
				$search_term
			);
		}

		$priority_clause = null;

		if ( ! empty( $priority ) ) {
			$priority_clause = $wpdb->prepare( "`priority` = %s AND", $priority );
		}

		$prepared_sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE $search_clause $priority_clause `date_created` <= %s ORDER BY `date_created` DESC, `id` DESC LIMIT %d, %d",
			$max_date,
			$offset,
			$per_page
		);

		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );

		return $this->hydrate( $results );
	}

	public function lines_as_data_grid( $lines ) {
		$return = array();
		$status_map = array(
			'fatal'   => array(
				'label'  => esc_html__( 'Fatal', 'gravitysmtp' ),
				'status' => 'error',
			),
			'error'   => array(
				'label'  => esc_html__( 'Error', 'gravitysmtp' ),
				'status' => 'error',
			),
			'warning' => array(
				'label'  => esc_html__( 'Warning', 'gravitysmtp' ),
				'status' => 'warning',
			),
			'debug'   => array(
				'label'  => esc_html__( 'Debug', 'gravitysmtp' ),
				'status' => 'active',
			),
			'info'    => array(
				'label'  => esc_html__( 'Info', 'gravitysmtp' ),
				'status' => 'gray',
			),
		);

		foreach ( $lines as $line ) {
			$priority = $line->priority();
			$status   = isset( $status_map[ $priority ] ) ? $status_map[ $priority ]['status'] : '';
			$label    = isset( $status_map[ $priority ] ) ? $status_map[ $priority ]['label'] : '';
			$return[] = array(
				'id' => array(
					'component' => 'Text',
					'props' => array(
						'content' => $line->id(),
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => $label,
						'status' => $status,
					),
				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $line->timestamp(),
						'size'    => 'text-sm',
					),
				),
				'log'     => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => $line->id() ),
						'disabled'      => false,
						'label'         => str_replace( '&quot;', '"', $line->line() ),
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props'     => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => $line->id() ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			);
		}

		return $return;
	}

	public function before( $date, $inclusive = true ) {
		global $wpdb;

		$comparator = $inclusive ? '<=' : '<';

		$sql = 'SELECT * FROM %1$s WHERE `date_created` %2$s "%3$s" ORDER BY %4$s DESC, %5$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), $comparator, $date, 'date_updated', 'id' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function after( $date, $inclusive = true ) {
		global $wpdb;

		$comparator = $inclusive ? '>=' : '>';

		$sql = 'SELECT * FROM %1$s WHERE `date_created` %2$s "%3$s" ORDER BY %4$s DESC, %5$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), $comparator, $date, 'date_updated', 'id' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function between( $start, $end, $inclusive = true ) {
		global $wpdb;

		$a_comp = $inclusive ? '<=' : '<';
		$b_comp = $inclusive ? '>=' : '>';

		$sql = 'SELECT * FROM %1$s WHERE `date_created` %2$s "%3$s" AND `date_created` %4$s "%5$s" ORDER BY %6$s DESC, %7$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), $b_comp, $start, $a_comp, $end, 'date_updated', 'id' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function by_priority( $priority ) {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s WHERE `priority` = %2$s ORDER BY %3$s DESC, %4$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), $priority, 'date_updated', 'id' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	protected function hydrate( $items ) {
		return array_map( function( $item ) {
			$parsed_date = get_date_from_gmt( $item['date_created'], 'Y-m-d H:i:s' );
			return new Log_Line( $parsed_date, $item['priority'], $item['line'], $item['id'] );
		}, $items );
	}

}
