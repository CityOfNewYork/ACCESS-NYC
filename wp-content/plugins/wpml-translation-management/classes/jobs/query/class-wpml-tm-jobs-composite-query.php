<?php
/**
 * WPML_TM_Jobs_Composite_Query class file.
 *
 * @package wpml-translation-management
 */

/**
 * Class WPML_TM_Jobs_Composite_Query
 */
class WPML_TM_Jobs_Composite_Query implements WPML_TM_Jobs_Query {
	const METHOD_UNION = 'union';
	const METHOD_COUNT = 'count';

	/**
	 * Job queries
	 *
	 * @var WPML_TM_Jobs_Composite_Query[]
	 */
	private $queries;

	/**
	 * Limit query helper
	 *
	 * @var WPML_TM_Jobs_Limit_Query_Helper
	 */
	private $limit_query_helper;

	/**
	 * Order query helper
	 *
	 * @var WPML_TM_Jobs_Order_Query_Helper
	 */
	private $order_query_helper;

	/**
	 * WPML_TM_Jobs_Composite_Query constructor.
	 *
	 * @param WPML_TM_Jobs_Composite_Query[]  $queries      Job queries.
	 * @param WPML_TM_Jobs_Limit_Query_Helper $limit_helper Limit helper.
	 * @param WPML_TM_Jobs_Order_Query_Helper $order_helper Order helper.
	 *
	 * @throws InvalidArgumentException In case of error.
	 */
	public function __construct(
		array $queries,
		WPML_TM_Jobs_Limit_Query_Helper $limit_helper,
		WPML_TM_Jobs_Order_Query_Helper $order_helper
	) {
		$queries = array_filter( $queries, array( $this, 'is_query_valid' ) );
		if ( empty( $queries ) ) {
			throw new InvalidArgumentException( 'Collection of sub-queries is empty or contains only invalid elements' );
		}

		$this->queries            = $queries;
		$this->limit_query_helper = $limit_helper;
		$this->order_query_helper = $order_helper;
	}


	/**
	 * Get data query
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 *
	 * @throws InvalidArgumentException In case of error.
	 * @return string
	 */
	public function get_data_query( WPML_TM_Jobs_Search_Params $params ) {
		if ( ! $params->get_job_types() ) {
			// We are merging subqueries here, that's why LIMIT must be applied to final query.
			$params_without_pagination_and_sorting = clone $params;
			$params_without_pagination_and_sorting->set_limit( 0 )->set_offset( 0 );
			$params_without_pagination_and_sorting->set_sorting( array() );

			$query = $this->get_sql( $params_without_pagination_and_sorting, self::METHOD_UNION );

			$order = $this->order_query_helper->get_order( $params );
			if ( $order ) {
				$query .= ' ' . $order;
			}
			$limit = $this->limit_query_helper->get_limit( $params );
			if ( $limit ) {
				$query .= ' ' . $limit;
			}

			return $query;
		} else {
			return $this->get_sql( $params, self::METHOD_UNION );
		}
	}

	/**
	 * Get count query
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 *
	 * @return int|string
	 */
	public function get_count_query( WPML_TM_Jobs_Search_Params $params ) {
		$params_without_pagination_and_sorting = clone $params;
		$params_without_pagination_and_sorting->set_limit( 0 )->set_offset( 0 );
		$params_without_pagination_and_sorting->set_sorting( array() );

		return $this->get_sql( $params_without_pagination_and_sorting, self::METHOD_COUNT );
	}

	/**
	 * Get SQL request string
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 * @param string                     $method Query method.
	 *
	 * @throws InvalidArgumentException In case of error.
	 * @throws RuntimeException In case of error.
	 * @return string
	 */
	private function get_sql( WPML_TM_Jobs_Search_Params $params, $method ) {
		switch ( $method ) {
			case self::METHOD_UNION:
				$query_method = 'get_data_query';
				break;
			case self::METHOD_COUNT:
				$query_method = 'get_count_query';
				break;
			default:
				throw new InvalidArgumentException( 'Invalid method argument: ' . $method );
		}

		$parts = array();
		foreach ( $this->queries as $query ) {
			$query_string = $query->$query_method( $params );
			if ( $query_string ) {
				$parts[] = $query_string;
			}
		}

		if ( ! $parts ) {
			throw new RuntimeException( 'None of subqueries matches to specified search parameters' );
		}

		if ( 1 === count( $parts ) ) {
			return current( $parts );
		}

		switch ( $method ) {
			case self::METHOD_UNION:
				return $this->get_union( $parts );
			case self::METHOD_COUNT:
				return $this->get_count( $parts );
		}

		return null;
	}

	/**
	 * Get union
	 *
	 * @param array $parts Query parts.
	 *
	 * @return string
	 */
	private function get_union( array $parts ) {
		return '( ' . implode( ' ) UNION ( ', $parts ) . ' )';
	}

	/**
	 * Get count
	 *
	 * @param array $parts Query parts.
	 *
	 * @return string
	 */
	private function get_count( array $parts ) {
		return 'SELECT ( ' . implode( ' ) + ( ', $parts ) . ' )';
	}

	/**
	 * Is query valid
	 *
	 * @param mixed $query SQL query.
	 *
	 * @return bool
	 */
	private function is_query_valid( $query ) {
		return $query instanceof WPML_TM_Jobs_Query;
	}
}
