<?php

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Relation;
use WPML\LIB\WP\User;
use WPML\TM\API\Translators;

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
	 * @param WP_REST_Request $request
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
	 * @param WP_REST_Request $request
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
		foreach ( [ 'id', 'source_language', 'translated_by', 'element_type' ] as $key ) {
			$value = (string) $request->get_param( $key );
			if ( $value ) {
				$params->{'set_' . $key}( $value );
			}
		}

		foreach ( [ 'ids', 'local_job_ids', 'title', 'target_language', 'batch_name' ] as $key ) {
			$value = (string) $request->get_param( $key );
			if ( strlen( $value ) ) {
				$params->{'set_' . $key}( explode( ',', $value ) );
			}
		}

		if ( $request->get_param( 'status' ) !== null ) {
			$statuses = Fns::map( Cast::toInt(), explode( ',', $request->get_param( 'status' ) ) );

			$params->set_status( Fns::reject( Relation::equals( ICL_TM_NEEDS_REVIEW ), $statuses ) );
			$params->set_needs_review( Lst::includes( ICL_TM_NEEDS_REVIEW, $statuses ) );
		}
		$params->set_exclude_cancelled();

		if ( $request->get_param( 'needs_update' ) ) {
			$params->set_needs_update( new WPML_TM_Jobs_Needs_Update_Param( $request->get_param( 'needs_update' ) ) );
		}

		if ( $request->get_param( 'only_automatic' ) ) {
			$params->set_exclude_manual( true );
		}

		$date_range_values = array( 'sent', 'deadline' );
		foreach ( $date_range_values as $date_range_value ) {
			$from = $request->get_param( $date_range_value . '_from' );
			$to   = $request->get_param( $date_range_value . '_to' );

			if ( $from || $to ) {
				$from = $from ? new DateTime( $from ) : null;
				$to   = $to ? new DateTime( $to ) : null;

				if ( $from && $to && $from > $to ) {
					continue;
				}

				$params->{'set_' . $date_range_value}( new WPML_TM_Jobs_Date_Range( $from, $to ) );
			}
		}

		if ( $request->get_param( 'pageName' ) === \WPML_TM_Jobs_List_Script_Data::TRANSLATION_QUEUE_PAGE ) {
			global $wpdb;

			/**
			 * On Translation Queue page, in general, you should only see the jobs assigned to you or unassigned.
			 * Although, we want to make an exception for automatic jobs which require review. Those jobs shall not have assigned translator,
			 * but due to some old bugs, a user can have corrupted data in the database. We want him to be able to see them even if due to the bug,
			 * they are assigned to somebody else.
			 */
			$translatorCond = "(
				(translate_job.translator_id = %d OR translate_job.translator_id = 0 OR translate_job.translator_id IS NULL) 
				OR (automatic = 1 OR review_status = 'NEEDS_REVIEW') 
			)";
			$where[] = $wpdb->prepare( $translatorCond, User::getCurrentId() );

			if ( ! $request->get_param( 'includeTranslationServiceJobs' ) ) {
				$where[] = 'translation_status.translation_service = "local"';
			}
			$where[] = "( automatic = 0 OR review_status = 'NEEDS_REVIEW' )";

			$where[] = $this->buildLanguagePairsCriteria();

			$params->set_custom_where_conditions( $where );
		}

		return $params;
	}

	/**
	 * @return string
	 */
	private function buildLanguagePairsCriteria() {
		$translator = Translators::getCurrent();

		$buildWhereForPair = function ( $targets, $source ) {
			return sprintf(
				'( translations.source_language_code = "%s" AND translations.language_code IN (%s) )',
				$source,
				wpml_prepare_in( $targets )
			);
		};

		return '( ' . \wpml_collect( $translator->language_pairs )
			->map( $buildWhereForPair )
			->implode( ' OR ' ) . ' ) ';
	}

	private function set_sorting( WPML_TM_Jobs_Search_Params $params, WP_REST_Request $request ) {
		$sorting = [];

		$sortingParams = $request->get_param( 'sorting' );
		if ( $sortingParams ) {
			$sorting = $this->build_sorting_params( $sortingParams );
		}

		if ( $request->get_param( 'pageName' ) === \WPML_TM_Jobs_List_Script_Data::TRANSLATION_QUEUE_PAGE ) {
			$sorting[] = new \WPML_TM_Jobs_Sorting_Param( "IF ((status = 10 AND (review_status = 'EDITING' OR review_status = 'NEEDS_REVIEW')) OR status = 1, 1,IF (status = 2, 2, IF (needs_update = 1, 3, 4)))", 'ASC' );
			$sorting[] = new \WPML_TM_Jobs_Sorting_Param( 'translator_id', 'DESC' );
			$sorting[] = new \WPML_TM_Jobs_Sorting_Param( 'translate_job_id', 'DESC' );
		}

		$params->set_sorting( $sorting );

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
