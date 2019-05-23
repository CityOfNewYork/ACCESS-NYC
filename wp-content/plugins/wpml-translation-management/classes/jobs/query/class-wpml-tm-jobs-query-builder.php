<?php

class WPML_TM_Jobs_Query_Builder {
	/** @var wpdb */
	private $wpdb;

	/** @var WPML_TM_Jobs_Limit_Query_Helper */
	protected $limit_helper;

	/** @var WPML_TM_Jobs_Order_Query_Helper */
	protected $order_helper;

	/** @var array */
	private $columns = array();

	/** @var string */
	private $from;

	/** @var array */
	private $joins = array();

	/** @var array */
	private $where = array();

	/** @var string */
	private $order;

	/** @var string */
	private $limit;

	/**
	 * @param WPML_TM_Jobs_Limit_Query_Helper $limit_helper
	 * @param WPML_TM_Jobs_Order_Query_Helper $order_helper
	 */
	public function __construct(
		WPML_TM_Jobs_Limit_Query_Helper $limit_helper,
		WPML_TM_Jobs_Order_Query_Helper $order_helper
	) {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->limit_helper = $limit_helper;
		$this->order_helper = $order_helper;
	}

	/**
	 * @param array $columns
	 *
	 * @return self
	 */
	public function set_columns( array $columns ) {
		$this->columns = $columns;

		return $this;
	}

	/**
	 * @param $column
	 *
	 * @return self
	 */
	public function add_column( $column ) {
		$this->columns[] = $column;

		return $this;
	}

	/**
	 * @param string $from
	 *
	 * @return self
	 */
	public function set_from( $from ) {
		$this->from = $from;

		return $this;
	}

	/**
	 * @param $join
	 *
	 * @return self
	 */
	public function add_join( $join ) {
		$this->joins[] = $join;

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return self
	 */
	public function set_status_filter( $column, WPML_TM_Jobs_Search_Params $params ) {
		if ( $params->get_status() ) {
			$statuses      = wpml_prepare_in( $params->get_status(), '%d' );
			$this->where[] = sprintf( $column . ' IN (%s)', $statuses );
		}

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return self
	 */
	public function set_scope_filter( $local, $remote, WPML_TM_Jobs_Search_Params $params ) {
		switch ( $params->get_scope() ) {
			case WPML_TM_Jobs_Search_Params::SCOPE_LOCAL:
				$this->where[] = $local;
				break;
			case WPML_TM_Jobs_Search_Params::SCOPE_REMOTE:
				$this->where[] = $remote;
				break;
		}

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return self
	 */
	public function set_title_filter( $column, WPML_TM_Jobs_Search_Params $params ) {
		if ( $params->get_title() ) {
			$title_where = array();
			foreach ( $params->get_title() as $title ) {
				$title_where[] = $this->wpdb->prepare(
					"{$column} LIKE %s",
					'%' . $title . '%'
				);
			}

			$this->where[] = '( ' . implode( ' OR ', $title_where ) . ' )';
		}

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return $this
	 */
	public function set_source_language( $column, WPML_TM_Jobs_Search_Params $params ) {
		if ( $params->get_source_language() ) {
			$this->where[] = $this->wpdb->prepare( "{$column} = %s", $params->get_source_language() );
		}

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return $this
	 */
	public function set_target_language( $column, WPML_TM_Jobs_Search_Params $params ) {
		if ( $params->get_target_language() ) {
			$this->where[] = sprintf(
				'%s IN (%s)',
				$column,
				wpml_prepare_in( $params->get_target_language() )
			);
		}

		return $this;
	}

	public function set_translated_by_filter(
		$local_translator_column,
		$translation_service_column,
		WPML_TM_Jobs_Search_Params $params
	) {
		if ( $params->get_scope() !== WPML_TM_Jobs_Search_Params::SCOPE_ALL && $params->get_translated_by() ) {
			if ( $params->get_scope() === WPML_TM_Jobs_Search_Params::SCOPE_LOCAL ) {
				$this->where[] = $this->wpdb->prepare(
					"{$local_translator_column} = %d",
					$params->get_translated_by()
				);
			} else {
				$this->where[] = $this->wpdb->prepare(
					"{$translation_service_column} = %d",
					$params->get_translated_by()
				);
			}
		}

		return $this;
	}

	/**
	 * @param string $column
	 * @param int    $value
	 *
	 * @return $this
	 */
	public function set_numeric_value_filter( $column, $value ) {
		if ( $value ) {
			$this->where[] = sprintf( "{$column} = %d", $value );
		}

		return $this;
	}

	/**
	 * @param                            $column
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return $this
	 */
	public function set_tp_id_filter( $column, WPML_TM_Jobs_Search_Params $params ) {
		if ( $params->get_tp_id() ) {
			$where  = array();
			$tp_ids = $params->get_tp_id();
			if ( in_array( null, $tp_ids, true ) ) {
				$tp_ids  = array_filter( $tp_ids );
				$where[] = $column . ' IS NULL';
			}

			if ( $tp_ids ) {
				$where[] = sprintf( $column . ' IN (%s)', wpml_prepare_in( $tp_ids ) );
			}

			$this->where[] = '( ' . implode( ' OR ', $where ) . ' )';
		}

		return $this;
	}

	/**
	 * @param string                  $column
	 * @param WPML_TM_Jobs_Date_Range $date_range
	 *
	 * @return self
	 */
	public function set_date_range( $column, WPML_TM_Jobs_Date_Range $date_range ) {
		$sql_parts = array();


		if ( $date_range->get_begin() ) {
			$sql_parts[] = $this->wpdb->prepare( $column . ' >= %s', $date_range->get_begin()->format( 'Y-m-d' ) );
		}
		if ( $date_range->get_end() ) {
			$sql_parts[] = $this->wpdb->prepare( $column . ' <= %s', $date_range->get_end()->format( 'Y-m-d 23:59:59' ) );
		}

		if ( $sql_parts ) {
			$sql = '( ' . implode( ' AND ', $sql_parts ) . ' )';

			if ( $date_range->is_include_null_date() ) {
				$sql .= " OR $column IS NULL";
				$sql = "( $sql )";
			}

			$this->where[] = $sql;
		}

		return $this;
	}

	/**
	 * @param string $where
	 *
	 * @return self
	 */
	public function add_AND_where_condition( $where ) {
		$this->where[] = $where;

		return $this;
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return self
	 */
	public function set_order( WPML_TM_Jobs_Search_Params $params ) {
		$this->order = $this->order_helper->get_order( $params );

		return $this;
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return self
	 */
	public function set_limit( WPML_TM_Jobs_Search_Params $params ) {
		$this->limit = $this->limit_helper->get_limit( $params );

		return $this;
	}

	public function build() {
		if ( ! $this->columns ) {
			throw new InvalidArgumentException( 'You have to specify columns list' );
		}

		if ( ! $this->from ) {
			throw new InvalidArgumentException( 'You have to specify FROM table' );
		}

		$sql = "
			SELECT
			%s
			FROM %s
		";
		$sql = sprintf( $sql, implode( ', ', $this->columns ), $this->from );

		if ( $this->joins ) {
			$sql .= implode( ' ', $this->joins );
		}
		if ( $this->where ) {
			$sql .= ' WHERE ' . implode( ' AND ', $this->where );
		}

		if ( $this->order ) {
			$sql .= ' ' . $this->order;
		}
		if ( $this->limit ) {
			$sql .= ' ' . $this->limit;
		}

		return $sql;
	}
}