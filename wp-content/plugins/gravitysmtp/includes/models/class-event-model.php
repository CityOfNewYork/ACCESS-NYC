<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Models\Hydrators\Hydrator_Factory;
use Gravity_Forms\Gravity_SMTP\Models\Traits\Can_Compare_Dynamically;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\SQL_Filter_Parser;

class Event_Model {

	use Can_Compare_Dynamically;

	protected $table_name = 'gravitysmtp_events';

	protected $tracking_table_name = 'gravitysmtp_event_tracking';

	/**
	 * @var Hydrator_Factory $hydrator_factory
	 */
	protected $hydrator_factory;

	/**
	 * @var Plugin_Opts_Data_Store
	 */
	protected $opts;

	/**
	 * @var Recipient_Parser
	 */
	protected $recipient_parser;

	/**
	 * The ID of the most-recently-created record.
	 *
	 * @var int
	 */
	protected $latest_id;

	/**
	 * @var SQL_Filter_Parser
	 */
	protected $filter_parser;

	protected $queryable = array(
		'id',
		'date_created',
		'date_updated',
		'service',
		'subject',
		'message',
		'status',
	);

	protected $fillable = array(
		'date_created',
		'date_updated',
		'service',
		'subject',
		'message',
		'extra',
		'status',
	);

	public function __construct( $hydrator_factory, $plugin_opts, $recipient_parser, $filter_parser ) {
		$this->hydrator_factory = $hydrator_factory;
		$this->opts             = $plugin_opts;
		$this->recipient_parser = $recipient_parser;
		$this->filter_parser    = $filter_parser;
	}

	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	protected function get_tracking_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->tracking_table_name;
	}

	public function get_latest_id() {
		return $this->latest_id;
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s ORDER BY %2$s DESC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'date_updated' ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function find( $where ) {
		global $wpdb;

		$post_hydrate_filters = array();
		$table_name           = $this->get_table_name();
		$tracking_table_name  = $wpdb->prefix . 'gravitysmtp_event_tracking';
		$values               = array();
		$sql                  = "SELECT mt.*, et.opened, et.clicked FROM $table_name AS mt LEFT JOIN $tracking_table_name AS et ON et.event_id = mt.id WHERE ";

		foreach ( $where as $condition ) {

			if ( count( $condition ) === 3 ) {
				$key        = $condition[0];
				$comparator = $condition[1];
				$value      = $condition[2];
			} else {
				$key        = $condition[0];
				$comparator = '=';
				$value      = $condition[1];
			}

			if ( ! in_array( $key, $this->queryable ) ) {
				$post_hydrate_filters[] = array( 'key' => $key, 'value' => $value, 'comparator' => $comparator );
				continue;
			}

			$key = sprintf( 'mt.%s', $key );

			$sql      .= sprintf( '%s %s %%s AND ', $key, $comparator );
			$values[] = $value;
		}

		$sql = rtrim( $sql, ' AND ' ) . ';';
		if ( strpos( $sql, '%s' ) !== false ) {
			$sql = $wpdb->prepare( $sql, $values );
		} else {
			$sql = rtrim( $sql, ' WHERE;' ) . ';';
		}
		$results  = $wpdb->get_results( $sql, ARRAY_A );
		$hydrated = $this->hydrate( $results );

		return array_filter( $hydrated, function ( $row ) use ( $post_hydrate_filters ) {
			foreach ( $post_hydrate_filters as $ph_condition ) {
				if ( ! isset( $row[ $ph_condition['key'] ] ) ) {
					return false;
				}

				$value_a = $row[ $ph_condition['key'] ];
				$value_b = $ph_condition['value'];

				if ( ! $this->compare( $value_a, $value_b, $ph_condition['comparator'] ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	public function slice( $count, $offset = 0 ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql     = "SELECT * FROM $table_name LIMIT %d, %d;";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $count ), ARRAY_A );

		return $this->hydrate( $results );
	}

	public function count( $search_term = null, $search_type = null, $filters = array() ) {
		global $wpdb;
		$table_name    = $this->get_table_name();
		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $this->get_search_clause( $search_term, $search_type );
			$search_clause = 'WHERE ' . preg_replace( '/ AND$/', '', $search_clause );
		}

		$filter_clause = null;

		if ( ! empty( $filters ) ) {
			$filter_clause = $this->filter_parser->process_filters( $filters );
			if ( empty( $search_clause ) ) {
				$filter_clause = 'WHERE ' . $filter_clause;
			} else {
				$filter_clause = ' AND ' . $filter_clause;
			}
		}

		$sql     = "SELECT COUNT(1) as 'count' FROM $table_name $search_clause $filter_clause;";
		$results = $wpdb->get_row( $sql, ARRAY_A );

		return $results['count'];
	}

	public function paginate( $page, $per_page, $max_date = false, $search_term = null, $search_type = null, $sort_by = null, $sort_order = null, $filters = array() ) {
		global $wpdb;
		$table_name          = $this->get_table_name();
		$tracking_table_name = $wpdb->prefix . 'gravitysmtp_event_tracking';
		$offset              = ( $page - 1 ) * $per_page;

		if ( ! $max_date ) {
			$max_date = current_time( 'mysql', true );
		}
		if ( empty( $sort_by ) ) {
			$sort_by = 'date_created';
		}
		if ( empty( $sort_order ) ) {
			$sort_order = 'DESC';
		}

		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $this->get_search_clause( $search_term, $search_type );
		}

		$filter_clause = null;

		if ( ! empty( $filters ) ) {
			$filter_clause = $this->filter_parser->process_filters( $filters, true );
		}

		$prepared_sql = $wpdb->prepare(
			"SELECT mt.*, et.opened, et.clicked FROM $table_name AS mt LEFT JOIN $tracking_table_name AS et ON et.event_id = mt.id WHERE $search_clause $filter_clause `date_created` <= %s ORDER BY `$sort_by` $sort_order LIMIT %d, %d",
			$max_date,
			$offset,
			$per_page
		);

		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );

		return $this->hydrate( $results );
	}

	private function get_search_clause( $search_term, $search_type ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		switch ( $search_type ) {
			case 'email_and_headers':
				$prepared_sql = $wpdb->prepare(
					"`extra` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			case 'content':
				$prepared_sql = $wpdb->prepare(
					"`message` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			case 'subject':
				$prepared_sql = $wpdb->prepare(
					"`subject` LIKE '%%%s%%' AND",
					$search_term
				);
				break;
			default:
				$prepared_sql = $wpdb->prepare(
					"( `subject` LIKE '%%%s%%' OR `message` LIKE '%%%s%%' OR `extra` LIKE '%%%s%%' ) AND",
					$search_term,
					$search_term,
					$search_term
				);
		}

		return $prepared_sql;
	}

	public function create( $service, $status, $to, $from, $subject, $message, $extra ) {
		if ( ! $this->is_logging_enabled() ) {
			return 0;
		}

		if ( ! $this->save_email_body() ) {
			$message                  = '';
			$extra['message_omitted'] = true;
		}

		global $wpdb;

		unset ( $extra['params']['body'] );

		$extra['to']   = $to;
		$extra['from'] = $from;

		$wpdb->insert(
			$this->get_table_name(),
			array(
				'date_created' => current_time( 'mysql', true ),
				'date_updated' => current_time( 'mysql', true ),
				'status'       => $status,
				'service'      => $service,
				'subject'      => $subject,
				'message'      => $message,
				'extra'        => serialize( $extra ),
			)
		);

		$created_id      = $wpdb->insert_id;
		$this->latest_id = $created_id;

		do_action( 'gravitysmtp_after_mail_created', $created_id, compact( 'service', 'status', 'to', 'from', 'subject', 'message', 'extra' ) );

		return $created_id;
	}

	public function get_records_over_limit( $limit ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT id FROM $table_name ORDER BY `date_created` DESC LIMIT %d, %d", $limit, PHP_INT_MAX );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	public function update( $values, $id ) {
		// Don't do anything for 0 id.
		if ( empty( $id ) ) {
			return;
		}

		if ( ! $this->save_email_body() ) {
			unset( $values['message'] );
			$extra                    = isset( $values['extra'] ) ? unserialize( $values['extra'] ) : array();
			$extra['message_omitted'] = true;
			$values['extra']          = serialize( $extra );
		}

		global $wpdb;

		$self   = $this;
		$values = array_filter( $values, function ( $key ) use ( $self ) {
			return in_array( $key, $self->fillable );
		}, ARRAY_FILTER_USE_KEY );

		$wpdb->update(
			$this->get_table_name(),
			$values,
			array( 'id' => $id )
		);

		do_action( 'gravitysmtp_after_mail_updated', $id, $values );
	}

	public function delete( $id ) {
		global $wpdb;

		$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0;" );
		$wpdb->delete( $this->get_table_name(), array( 'id' => $id ) );
	}

	public function delete_before( $date ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$query      = $wpdb->prepare( "DELETE FROM $table_name WHERE `date_created` <= %s", get_gmt_from_date( $date ) );

		$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0;" );
		$wpdb->query( $query );
	}

	public function delete_all() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0;" );
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	public function get_counts() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql            = "SELECT service, COUNT(id) AS count FROM {$table_name} GROUP BY service";
		$service_counts = $wpdb->get_results( $sql, ARRAY_A );

		$sql           = "SELECT status, COUNT(id) AS count FROM {$table_name} GROUP BY status";
		$status_counts = $wpdb->get_results( $sql, ARRAY_A );

		$total = array_sum( wp_list_pluck( $service_counts, 'count' ) );

		return array(
			'total'   => $total,
			'service' => $service_counts,
			'status'  => $status_counts,
		);
	}

	protected function hydrate( $rows ) {
		static $hydrators = array();

		foreach ( $rows as $idx => $row ) {
			$service = $row['service'];

			if ( $service === 'amazon-ses' ) {
				$service = 'amazon';
			}

			// Cleanup bad old data.
			if ( $row['service'] === 'phpmail' ) {
				$row['service'] = 'php';
			}

			if ( ! isset( $row['opened'] ) || is_null( $row['opened'] ) ) {
				$row['opened'] = __( 'No', 'gravitysmtp' );
			} else {
				$row['opened'] = $row['opened'] ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' );
			}

			if ( ! isset( $row['clicked'] ) || is_null( $row['clicked'] ) ) {
				$row['clicked'] = __( 'No', 'gravitysmtp' );
			} else {
				$row['clicked'] = $row['clicked'] ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' );
			}

			$extra = strpos( $row['extra'], '{' ) === 0 ? json_decode( $row['extra'], true ) : unserialize( $row['extra'] );

			try {
				if ( isset( $hydrators[ $service ] ) ) {
					$hydrator = $hydrators[ $service ];
				} else {
					$hydrator              = $this->hydrator_factory->create( $service );
					$hydrators[ $service ] = $hydrator;
				}
			} catch ( \Exception $e ) {
				$hydrator = false;
			}

			$row['source']       = isset( $extra['source'] ) ? $extra['source'] : __( 'N/A', 'gravitysmtp' );
			$row['email_counts'] = $this->get_email_counts( $extra );
			$row['can_resend']   = empty( $extra['message_omitted'] ) && ( empty( $extra['attachments'] ) || ( ! empty( $extra['attachments_saved'] ) ) );

			if ( $hydrator ) {
				$rows[ $idx ] = $hydrator->hydrate( $row );
			} else {
				$rows[ $idx ] = $row;
			}
		}

		return $rows;
	}

	public function get( $id ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %s", $id );

		$email = $wpdb->get_row( $sql, ARRAY_A );

		$hydrated = $this->hydrate( array( $email ) );

		return $hydrated[0];
	}

	public function get_earliest_event_date() {
		static $found;

		if ( ! empty( $found ) ) {
			return $found;
		}

		global $wpdb;
		$table_name = $this->get_table_name();

		$sql     = $wpdb->prepare( "SELECT date_created FROM $table_name ORDER BY date_created ASC LIMIT %d, %d", 0, 1 );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $results ) ) {
			return gmdate( 'Y-m-d 00:00:00', time() );
		}

		$found = get_date_from_gmt( $results[0]['date_created'] );

		return gmdate( 'Y-m-d 00:00:00', strtotime( $found ) );
	}

	public function get_top_sending_sources( $start, $end ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT SUBSTRING_INDEX(
								SUBSTRING_INDEX( SUBSTRING(extra, (INSTR(extra, CONCAT('source', '\";')) + CHAR_LENGTH('source') + 1)), '\"', 2),
								'\"', -1) as source,
								count(*) as total
								FROM ( SELECT * FROM $table_name WHERE date_created >= %s AND date_created <= %s ORDER BY date_created DESC LIMIT 0, 5000 ) AS timeboxed
								GROUP BY source
								ORDER BY total DESC
								LIMIT 0, 8", get_gmt_from_date( $start ), get_gmt_from_date( $end ) );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	public function get_all_sending_sources() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT SUBSTRING_INDEX(
								SUBSTRING_INDEX( SUBSTRING(extra, (INSTR(extra, CONCAT('source', '\";')) + CHAR_LENGTH('source') + 1)), '\"', 2),
								'\"', -1) as source
								FROM ( SELECT * FROM $table_name LIMIT 0, %d ) AS timeboxed
								GROUP BY source", 5000 );

		$results = $wpdb->get_results( $sql, ARRAY_A );
		$sources = wp_list_pluck( $results, 'source' );

		$filtered = array_filter( $sources, function ( $source ) {
			if ( strpos( $source, ':[' ) !== false || strpos( $source, ':{' ) !== false ) {
				return false;
			}

			if ( $source === 'headers' || $source === 'params' || $source === 'to' || $source === 'message_omitted' ) {
				return false;
			}

			return true;
		} );

		return $filtered;
	}

	public function get_top_recipients( $start, $end ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT SUBSTRING_INDEX(
				               	SUBSTRING_INDEX( SUBSTRING(extra, (INSTR(extra, CONCAT('email', '\";')) + CHAR_LENGTH('email') + 1)), '\"', 2),
				               	'\"', -1) as recipients,
						       	count(*) as total
								FROM ( SELECT * FROM $table_name WHERE date_created >= %s AND date_created <= %s ORDER BY date_created DESC LIMIT 0, 5000 ) AS timeboxed
								GROUP BY recipients
								ORDER BY total DESC
								LIMIT 0, 8", get_gmt_from_date( $start ), get_gmt_from_date( $end ) );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	public function get_event_stats( $start, $end ) {
		global $wpdb;

		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT status, count( * ) AS total FROM ( SELECT * FROM $table_name WHERE date_created >= %s AND date_created <= %s AND status != 'pending' ORDER BY date_created DESC ) AS timeboxed GROUP BY status", get_gmt_from_date( $start ), get_gmt_from_date( $end ) );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$return = array(
			'failed' => 0,
			'sent' => 0,
		);

		// Map to key/value pair.
		foreach ( $results as $result ) {
			$return[ $result['status'] ] = (int) $result['total'];
		}

		return $return;
	}

	public function get_opens_for_period( $start, $end ) {
		global $wpdb;

		$table_name          = $this->get_table_name();
		$tracking_table_name = $wpdb->prefix . 'gravitysmtp_event_tracking';

		$sql = $wpdb->prepare( "SELECT count( * ) AS total FROM ( SELECT * FROM $table_name WHERE date_created >= %s AND date_created <= %s ORDER BY date_created DESC ) AS timeboxed LEFT JOIN $tracking_table_name AS tt ON tt.event_id = timeboxed.id WHERE tt.opened = 1", get_gmt_from_date( $start ), get_gmt_from_date( $end ) );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $results ) ) {
			return 0;
		}

		return (int) $results[0]['total'];
	}

	public function get_chart_data( $start, $end ) {
//		switch ( $period ) {
//			case 'day':
//			default:
//				$format = '%b %d';
//				break;
//			case 'month':
//				$format = '%b %Y';
//				break;
//			case 'hour':
//				$format = '%H:00 %b %d';
//				break;
//		}
//
		global $wpdb;

		$table_name = $this->get_table_name();

//		$sql = $wpdb->prepare( "SELECT count(*) as total, DATE_FORMAT(date_created, %s) as date_created, date_created as sort_date, status FROM ( SELECT * FROM $table_name WHERE date_created >= %s AND date_created <= %s AND status != 'pending' ) AS timeboxed GROUP BY DATE_FORMAT(date_created, %s), status", $format, get_gmt_from_date( $start ), get_gmt_from_date( $end ), $format );
		$sql = $wpdb->prepare( "SELECT date_created, status FROM $table_name WHERE date_created >= %s AND date_created <= %s AND status != 'pending'", get_gmt_from_date( $start ), get_gmt_from_date( $end ) );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	private function get_email_counts( $extra ) {
		return $this->recipient_parser->get_email_counts( $extra );
	}

	private function is_logging_enabled() {
		$logging_enabled = $this->opts->get( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, 'config', 'true' );

		if ( empty( $logging_enabled ) ) {
			$logging_enabled = true;
		} else {
			$logging_enabled = $logging_enabled !== 'false';
		}

		return $logging_enabled;
	}

	private function save_email_body() {
		$save_email_body = $this->opts->get( Save_Plugin_Settings_Endpoint::PARAM_SAVE_EMAIL_BODY_ENABLED, 'config', 'true' );

		return empty( $save_email_body ) ? true : $save_email_body !== 'false';
	}

	public function set_clicked( $email_id, $email_address, $is = 1 ) {
		global $wpdb;

		$table_name = $this->get_tracking_table_name();

		$record            = $this->get_existing_tracking_entry( $email_id, $email_address );
		$record['clicked'] = $is;

		$this->insert_tracking_record( $record );
	}

	public function set_opened( $email_id, $email_address, $is = 1 ) {
		global $wpdb;

		$table_name = $this->get_tracking_table_name();

		$record           = $this->get_existing_tracking_entry( $email_id, $email_address );
		$record['opened'] = $is;

		$this->insert_tracking_record( $record );
	}

	private function insert_tracking_record( $record ) {
		global $wpdb;

		$table_name = $this->get_tracking_table_name();

		$wpdb->replace(
			$table_name,
			$record
		);
	}

	private function get_existing_tracking_entry( $email_id, $email_address ) {
		global $wpdb;

		$table_name = $this->get_tracking_table_name();

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE event_id = %s AND email = %s", $email_id, $email_address );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $results ) ) {
			return array(
				'event_id' => $email_id,
				'email'    => $email_address,
				'opened'   => 0,
				'clicked'  => 0,
			);
		}

		return $results[0];
	}

}
