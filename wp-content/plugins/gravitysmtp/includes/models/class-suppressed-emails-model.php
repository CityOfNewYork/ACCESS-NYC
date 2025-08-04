<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

use Gravity_Forms\Gravity_SMTP\Enums\Suppression_Reason_Enum;
use Gravity_Forms\Gravity_SMTP\Users\Roles;

class Suppressed_Emails_Model {

	protected $table_name = 'gravitysmtp_suppressed_emails';

	protected $fillable = array(
		'email',
		'reason',
		'notes',
	);

	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	public function suppress_email( $email, $reason, $notes = '' ) {

		/**
		 * Allows third-parties to abort suppressing a given email based on the data.
		 *
		 * @since 1.5.1
		 *
		 * @param string $email
		 * @param string $reason
		 * @param string $notes
		 *
		 * @return boolean
		 */
		$skip = apply_filters( 'gravitysmtp_abort_email_suppression', false, $email, $reason, $notes );

		if ( $skip ) {
			return;
		}

		global $wpdb;
		$table_name = $this->get_table_name();

		$data = array(
			'date_created' => current_time( 'mysql', true ),
			'email'        => $email,
			'reason'       => $reason,
			'notes'        => $notes,
		);

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE email = %s", $email );
		$existing = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! empty( $existing ) ) {
			$data = array(
				'notes' => $notes,
			);

			$this->update( $email, array( 'notes' => $notes ) );
			return $existing[0]['id'];
		}


		$wpdb->insert(
			$table_name,
			array(
				'date_created' => current_time( 'mysql', true ),
				'email'        => $email,
				'reason'       => $reason,
				'notes'        => $notes,
			)
		);

		$created_id = $wpdb->insert_id;

		/**
		 * Allows third-parties to perform an action after an email is suppressed.
		 *
		 * @since 1.5.1
		 *
		 * @param string $email
		 * @param string $reason
		 * @param string $notes
		 *
		 * @return boolean
		 */
		do_action( 'gravitysmtp_after_email_suppressed', $created_id, $email, $reason, $notes );

		return $created_id;
	}

	public function is_email_suppressed( $email ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE email = %s", $email );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return ! empty( $results );
	}

	public function update( $email, $values ) {
		global $wpdb;

		$self   = $this;
		$values = array_filter( $values, function ( $key ) use ( $self ) {
			return in_array( $key, $self->fillable );
		}, ARRAY_FILTER_USE_KEY );

		$wpdb->update(
			$this->get_table_name(),
			$values,
			array( 'email' => $email )
		);
	}

	public function reactivate_email( $email ) {
		global $wpdb;

		$wpdb->delete( $this->get_table_name(), array( 'email' => $email ) );
	}

	public function delete_all() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s ORDER BY %2$s ASC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'date_created' ), ARRAY_A );

		return $results;
	}

	public function paginate( $page, $per_page, $search_term = null, $sort_by = null, $sort_order = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$offset = ( $page - 1 ) * $per_page;

		if ( empty( $sort_by ) ) {
			$sort_by = 'date_created';
		}
		if ( empty( $sort_order ) ) {
			$sort_order = 'DESC';
		}

		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause .= $wpdb->prepare( "WHERE email = %s", $search_term );
		}

		$prepared_sql = $wpdb->prepare(
			"SELECT * FROM $table_name $search_clause ORDER BY `$sort_by` $sort_order LIMIT %d, %d",
			$offset,
			$per_page
		);

		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );

		return $results;
	}

	public function count( $search_term = null ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		$search_clause = null;

		if ( ! empty( $search_term ) ) {
			$search_clause = $wpdb->prepare( "WHERE MATCH(email,notes) AGAINST(%s IN NATURAL LANGUAGE MODE)", $search_term );
		}

		$sql = "SELECT COUNT(1) as 'count' FROM $table_name $search_clause;";
		$results = $wpdb->get_row( $sql, ARRAY_A );

		return $results['count'];
	}

	public function format_as_data_rows( $data ) {
		$rows = array();

		foreach ( $data as $row ) {
			$grid_actions = $this->get_suppression_grid_actions( $row['id'] );

			$row_data = array(
				'id'      => $row['id'],
				'email'   => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $row['email'],
						'size'    => 'text-sm',
					),
				),
				'reason'  => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'label'  => Suppression_Reason_Enum::label( $row['reason'] ),
						'status' => Suppression_Reason_Enum::indicator( $row['reason'] ),
						'hasDot' => false,
					),
				),
				'date'    => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $this->convert_dates_to_timezone( $row['date_created'] ),
						'size'    => 'text-sm',
					),
				),
				'notes'   => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $row['notes'],
						'size'    => 'text-sm',
					),
				),
				'actions' => $grid_actions,
			);

			$rows[] = $row_data;
		}

		return $rows;
	}

	private function convert_dates_to_timezone( $date ) {
		$gmt_time   = new \DateTimeZone( 'UTC' );
		$local_time = new \DateTimeZone( wp_timezone_string() );
		$datetime   = new \DateTime( $date, $gmt_time );
		$datetime->setTimezone( $local_time );

		return $datetime->format( 'F d, Y \a\t h:ia' );
	}

	private function get_suppression_grid_actions( $email_id ) {
		$actions = array(
			'component'  => 'Box',
			'components' => array(
				array(
					'component' => 'Button',
					'props'     => array(
						'action'           => 'reactivate',
						'customAttributes' => array(
							'title' => esc_html__( 'Reactivate', 'gravitysmtp' ),
						),
						'customClasses'    => array( 'gravitysmtp-data-grid__action' ),
						'icon'             => 'reactivate',
						'iconPrefix'        => 'gravitysmtp-admin-icon',
						'size'             => 'size-height-s',
						'type'             => 'icon-white',
						'data'             => array(
							'email_id' => $email_id,
						),
					),
				),
			),
		);

		return apply_filters( 'gravitysmtp_email_suppression_actions', $actions );
	}
}
