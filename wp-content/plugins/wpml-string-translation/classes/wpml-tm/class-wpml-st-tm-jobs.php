<?php

class WPML_ST_TM_Jobs extends WPML_WPDB_User {

	/**
	 * @param wpdb $wpdb
	 * WPML_ST_TM_Jobs constructor.
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		add_filter(
			'wpml_tm_jobs_union_table_sql',
			array(
				$this,
				'jobs_union_table_sql_filter',
			),
			10,
			2
		);
		add_filter(
			'wpml_post_translation_original_table',
			array(
				$this,
				'filter_tm_post_job_table',
			),
			10,
			1
		);
	}

	public function jobs_union_table_sql_filter( $sql_statements, $args ) {
		return $this->get_jobs_table_sql_part( $sql_statements, $args );
	}

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public function filter_tm_post_job_table( $table ) {
		return " (SELECT ID, post_type FROM {$table}
						UNION ALL
					SELECT ID, NULL as post_type FROM {$this->wpdb->prefix}icl_string_packages) ";
	}

	private function get_jobs_table_sql_part( $sql_statements, $args ) {
		$sql_statements[] = "SELECT  st.translator_id,
					st.id AS job_id,
					'string' AS element_type_prefix,
					NULL AS post_type,
					st.batch_id
			FROM {$this->wpdb->prefix}icl_string_translations st
				JOIN {$this->wpdb->prefix}icl_strings s
					ON s.id = st.string_id
      " . $this->build_string_where( $args );

		return $sql_statements;
	}

	/**
	 * @param array $args
	 *          string_where
	 *          translator_id
	 *          from
	 *          to
	 *          status
	 *          service
	 *
	 * @return string
	 */
	private function build_string_where( $args ) {
		if ( isset( $args['overdue'] ) && $args['overdue'] ) {
			// We do not save "deadline" for string jobs so we just want to exclude them in such case
			return 'WHERE 1 = 0';
		}

		$string_where  = isset( $args['string_where'] ) ? $args['string_where'] : '';
		$translator_id = isset( $args['translator_id'] ) ? $args['translator_id'] : '';
		$from          = isset( $args['from'] ) ? $args['from'] : '';
		$to            = isset( $args['to'] ) ? $args['to'] : '';
		$status        = isset( $args['status'] ) ? $args['status'] : '';
		$service       = isset( $args['service'] ) ? $args['service'] : false;

		$wheres     = array();
		$where_args = array();

		if ( true === (bool) $from ) {
			$wheres[]     = 's.language = %s';
			$where_args[] = $from;
		}
		if ( true === (bool) $to ) {
			$wheres[]     = 'st.language = %s';
			$where_args[] = $to;
		}
		if ( $status ) {
			$wheres[]     = 'st.status = %d';
			$where_args[] = ICL_TM_IN_PROGRESS === (int) $status ? ICL_TM_WAITING_FOR_TRANSLATOR : $status;
		}

		$service = ! $service && is_numeric( $translator_id ) ? 'local' : $service;
		$service = 'local' !== $service && false !== strpos( $translator_id, 'ts-' ) ? substr( $translator_id, 3 ) : $service;

		if ( 'local' === $service ) {
			$wheres[]     = 'st.translator_id = %s';
			$where_args[] = $translator_id;
		}

		if ( false !== $service ) {
			$wheres[]     = 'st.translation_service = %s';
			$where_args[] = $service;
		}

		if ( count( $wheres ) > 0 && count( $wheres ) === count( $where_args ) ) {
			$where_sql = implode( ' AND ', $wheres );

			/** @var string $sql */
			$sql = $this->wpdb->prepare( $where_sql, $where_args );
			$string_where = 'WHERE ' . $sql;
		}

		return $string_where;
	}
}
