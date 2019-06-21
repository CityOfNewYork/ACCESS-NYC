<?php

class WPML_Custom_Field_Setting_Query {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var array $excluded_keys */
	private $excluded_keys;

	/** @var string $table */
	private $table;

	/**
	 * @param wpdb   $wpdb
	 * @param array  $excluded_keys
	 * @param string $table
	 */
	public function __construct( wpdb $wpdb, array $excluded_keys, $table ) {
		$this->wpdb          = $wpdb;
		$this->excluded_keys = $excluded_keys;
		$this->table         = $table;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get( array $args ) {
		$args = array_merge(
			array(
				'search'             => null,
				'hide_system_fields' => false,
				'items_per_page'     => null,
				'page'               => null,
			),
			$args
		);

		$where = " WHERE 1=1";
		$where .= $this->add_AND_excluded_fields_condition();
		$where .= $this->add_AND_search_condition( $args['search'] );
		$where .= $this->add_AND_system_fields_condition( $args['hide_system_fields'] );

		$limit_offset = $this->get_limit_offset( $args );

		$query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT meta_key FROM {$this->table}" . $where . $limit_offset;

		return $this->wpdb->get_col( $query );
	}

	/**
	 * @return int
	 */
	public function get_total_rows() {
		return (int) $this->wpdb->get_var( "SELECT FOUND_ROWS();" );
	}

	/**
	 * @return string
	 */
	private function add_AND_excluded_fields_condition() {
		if ( $this->excluded_keys ) {
			return ' AND meta_key NOT IN(' . wpml_prepare_in( $this->excluded_keys ) . ')';
		}

		return '';
	}

	/**
	 * @param string $search
	 *
	 * @return string
	 */
	private function add_AND_search_condition( $search ) {
		if ( $search ) {
			return $this->wpdb->prepare( ' AND meta_key LIKE "%s"', '%' . $search . '%' );
		}

		return '';
	}

	/**
	 * @param bool   $hide_system_fields
	 *
	 * @return string
	 */
	private function add_AND_system_fields_condition( $hide_system_fields ) {
		if ( $hide_system_fields ) {
			return ' AND meta_key NOT LIKE "\_%"';
		}

		return '';
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	private function get_limit_offset( array $args ) {
		$limit_offset = '';

		if ( $args['items_per_page'] && 0 < (int) $args['page'] ) {
			$limit_offset = $this->wpdb->prepare(
				' LIMIT %d OFFSET %d',
				$args['items_per_page'],
				( $args['page'] - 1 ) * $args['items_per_page']
			);
		}

		return $limit_offset;
	}
}
