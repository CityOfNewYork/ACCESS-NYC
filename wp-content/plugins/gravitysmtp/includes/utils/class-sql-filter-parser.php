<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class SQL_Filter_Parser {

	protected $queryable = array(
		'id',
		'date_created',
		'date_updated',
		'service',
		'subject',
		'message',
		'status',
	);

	protected $date_queryable = array(
		'date_created',
		'date_updated',
	);

	public function process_filters( $filters, $trailing_union = false, $union = 'AND', $passed_key = null ) {
		global $wpdb;

		$sql_array = array();

		foreach ( $filters as $key => $value ) {

			if ( in_array( $key, $this->date_queryable ) && is_array( $value ) ) {
				$from = $value[0];
				$to   = $value[1];

				if ( $from == $to ) {
					$from = sprintf( '%s 00:00:00', $from );
					$to   = sprintf( '%s 23:59:59', $to );
				}

				$from = get_gmt_from_date( $from );
				$to   = get_gmt_from_date( $to );

				$sql_array[] = $wpdb->prepare( "`" . $key . "` BETWEEN %s AND %s", $from, $to );
				continue;
			}

			if ( is_array( $value ) ) {
				$sql_array[] = $this->process_filters( $value, false, 'OR', $key );
				continue;
			}

			if ( $passed_key !== null ) {
				$key = $passed_key;
			}

			if ( $key === 'attachments' ) {
				$inverter    = $value === 'no' ? null : 'NOT';
				$sql_array[] = '`extra` ' . $inverter . ' LIKE "%\"attachments\";a:0:%"';
				continue;
			}

			if ( in_array( $key, $this->queryable ) ) {
				$sql_array[] = $wpdb->prepare( "`" . $key . "` = %s", $value );
				continue;
			}

			$sql_array[] = sprintf( '`extra` RLIKE \'"%s"[^"]+"%s"\'', $key, $value );
		}

		$sql = implode( ' ' . $union . ' ', $sql_array );

		$sql = "($sql)";

		if ( $trailing_union ) {
			$sql .= ' ' . $union . ' ';
		}

		return $sql;
	}

}