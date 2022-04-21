<?php

class WPML_TM_Rest_Jobs_Criteria_Parser {
	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WPML_TM_Jobs_Search_Params
	 */
	public function build_criteria( WP_REST_Request $request ) {
		$params = new WPML_TM_Jobs_Search_Params();

		$params = $this->set_scope( $params, $request );
		$params = $this->set_pagination( $params, $request );
		$params = $this->set_filters( $params, $request );
		$params = $this->set_sorting( $params, $request );

		return $params;
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 * @param WP_REST_Request            $request
	 *
	 * @return WPML_TM_Jobs_Search_Params
	 */
	private function set_scope( WPML_TM_Jobs_Search_Params $params, WP_REST_Request $request ) {
		$scope = $request->get_param( 'scope' );
		if ( WPML_TM_Jobs_Search_Params::is_valid_scope( $scope ) ) {
			$params->set_scope( $scope );
		}

		return $params;
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 * @param WP_REST_Request            $request
	 *
	 * @return WPML_TM_Jobs_Search_Params
	 */
	private function set_pagination( WPML_TM_Jobs_Search_Params $params, WP_REST_Request $request ) {
		$limit = (int) $request->get_param( 'limit' );
		if ( $limit > 0 ) {
			$params->set_limit( $limit );

			$offset = (int) $request->get_param( 'offset' );
			if ( $offset > 0 ) {
				$params->set_offset( $offset );
			}
		}

		return $params;
	}

	private function set_filters( WPML_TM_Jobs_Search_Params $params, WP_REST_Request $request ) {
		foreach ( [ 'id', 'source_language', 'translated_by' ] as $key ) {
			$value = (string) $request->get_param( $key );
			if ( $value ) {
				$params->{'set_' . $key}( $value );
			}
		}

		foreach ( [ 'local_job_ids', 'title', 'target_language', 'status', 'batch_name' ] as $key ) {
			$value = (string) $request->get_param( $key );
			if ( strlen( $value ) ) {
				$params->{'set_' . $key}( explode( ',', $value ) );
			}
		}

		if ( $request->get_param( 'needs_update' ) ) {
			$params->set_needs_update( new WPML_TM_Jobs_Needs_Update_Param( $request->get_param( 'needs_update' ) ) );
		}

		$date_range_values = array( 'sent', 'deadline' );
		foreach ( $date_range_values as $date_range_value ) {
			$from = $request->get_param( $date_range_value . '_from' );
			$to   = $request->get_param( $date_range_value . '_to' );

			if ( $from || $to ) {
				$from = $from ? new DateTime( $from ) : $from;
				$to   = $to ? new DateTime( $to ) : $to;

				if ( $from && $to && $from >= $to ) {
					continue;
				}

				$params->{'set_' . $date_range_value}( new WPML_TM_Jobs_Date_Range( $from, $to ) );
			}
		}

		return $params;
	}

	private function set_sorting( WPML_TM_Jobs_Search_Params $params, WP_REST_Request $request ) {
		$sorting = $request->get_param( 'sorting' );
		if ( $sorting ) {
			$params->set_sorting( $this->build_sorting_params( $sorting ) );
		}

		return $params;
	}

	/**
	 * @param array $request_param
	 *
	 * @return WPML_TM_Jobs_Sorting_Param[]
	 */
	private function build_sorting_params( array $request_param ) {
		return \wpml_collect( $request_param )->map(
			function ( $direction, $column ) {
				return new WPML_TM_Jobs_Sorting_Param( $column, $direction );
			}
		)->toArray();
	}
}
