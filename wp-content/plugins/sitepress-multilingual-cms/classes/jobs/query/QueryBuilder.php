<?php

namespace WPML\TM\Jobs\Query;

use \wpdb;
use WPML\TM\ATE\Review\ReviewStatus;
use \WPML_TM_Jobs_Search_Params;
use \WPML_TM_Jobs_Date_Range;
use \InvalidArgumentException;

class QueryBuilder {
	/** @var wpdb */
	private $wpdb;

	/** @var LimitQueryHelper */
	protected $limit_helper;

	/** @var OrderQueryHelper */
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
	 * @param LimitQueryHelper $limit_helper
	 * @param OrderQueryHelper $order_helper
	 */
	public function __construct(
		LimitQueryHelper $limit_helper,
		OrderQueryHelper $order_helper
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
	 * @param string     $column
	 * @param array|null $values
	 *
	 * @return $this
	 */
	public function set_multi_value_text_filter( $column, $values ) {
		if ( $values ) {
			$where = \wpml_collect( $values )->map(
				function ( $value ) use ( $column ) {
					return $this->wpdb->prepare( "{$column} LIKE %s", '%' . $value . '%' );
				}
			)->toArray();

			$this->where[] = '( ' . implode( ' OR ', $where ) . ' )';
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
	 * @param string    $column
	 * @param int|int[] $value
	 *
	 * @return $this
	 */
	public function set_numeric_value_filter( $column, $value ) {
		if ( $value ) {
			if ( is_array( $value ) ) {
				$this->where[] = sprintf( "{$column} IN(%s)", wpml_prepare_in( $value, '%d' ) );
			} else {
				$this->where[] = sprintf( "{$column} = %d", $value );
			}
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
			$sql_parts[] = $this->wpdb->prepare(
				$column . ' <= %s',
				$date_range->get_end()->format( 'Y-m-d 23:59:59' )
			);
		}

		if ( $sql_parts ) {
			$sql = '( ' . implode( ' AND ', $sql_parts ) . ' )';

			if ( $date_range->is_include_null_date() ) {
				$sql .= " OR $column IS NULL";
				$sql  = "( $sql )";
			}

			$this->where[] = $sql;
		}

		return $this;
	}

	public function set_needs_review() {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translation_status.review_status = %s OR translation_status.review_status = %s)',
				ReviewStatus::NEEDS_REVIEW,
				ReviewStatus::EDITING
			)
		);
	}

	public function set_do_not_need_review() {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translation_status.review_status IS NULL OR translation_status.review_status = %s)',
				ReviewStatus::ACCEPTED
			)
		);
	}

	public function set_max_retries( $maxRetries ) {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translation_status.ate_comm_retry_count <= %d )',
				$maxRetries
			)
		);
	}

	public function set_element_type( $element_type ) {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translations.element_type = %s )',
				$element_type
			)
		);
	}

	/**
	 * @param bool $automatic
	 */
	public function set_automatic( $automatic = true ) {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translate_job.automatic = %d )',
				$automatic ? 1 : 0
			)
		);
	}

	/**
	 * @param int $maxAteSyncCount
	 */
	public function set_max_ate_sync_count( $maxAteSyncCount ) {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'(translate_job.ate_sync_count <= %d )',
				$maxAteSyncCount
			)
		);
	}

	public function set_set_excluded_jobs() {
		$this->add_AND_where_condition(
			$this->wpdb->prepare(
				'translation_status.status != %s',
				\WPML_TM_ATE_API::SHOULD_HIDE_STATUS
			)
		);
	}

	/**
	 * @param string|void $where
	 *
	 * @return self
	 */
	public function add_AND_where_condition( $where ) {
		if ( $where ) {
			$this->where[] = $where;
		}

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

		$sql = '
			SELECT
			%s
			FROM %s
		';
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
