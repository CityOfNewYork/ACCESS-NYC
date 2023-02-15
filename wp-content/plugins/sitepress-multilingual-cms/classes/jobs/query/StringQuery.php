<?php
/**
 * WPML_TM_Jobs_String_Query class file
 *
 * @package wpml-translation-management
 */

namespace WPML\TM\Jobs\Query;

use \wpdb;
use \WPML_TM_Jobs_Search_Params;
use \WPML_TM_Job_Entity;

class StringQuery implements Query {

	/**
	 * WP database instance
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Query builder instance
	 *
	 * @var QueryBuilder
	 */
	protected $query_builder;

	/** @var string */
	protected $batch_name_column = 'batches.batch_name';

	/**
	 * @param wpdb         $wpdb          WP database instance.
	 * @param QueryBuilder $query_builder Query builder instance.
	 */
	public function __construct( wpdb $wpdb, QueryBuilder $query_builder ) {
		$this->wpdb          = $wpdb;
		$this->query_builder = $query_builder;
	}

	/**
	 * Get data query
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 *
	 * @return string
	 */
	public function get_data_query( WPML_TM_Jobs_Search_Params $params ) {
		$columns = array(
			'string_translations.id as id',
			'"' . WPML_TM_Job_Entity::STRING_TYPE . '" as type',
			'string_status.rid as tp_id',
			'batches.id as local_batch_id',
			'batches.tp_id as tp_batch_id',
			$this->batch_name_column,
			'string_translations.status as status',
			'strings.id as original_element_id',
			'strings.language as source_language',
			'string_translations.language as target_language',
			'string_translations.translation_service as translation_service',
			'string_status.timestamp as sent_date',
			'NULL as deadline_date',
			'NULL as completed_date',
			'strings.value as title',
			'source_languages.english_name as source_language_name',
			'target_languages.english_name as target_language_name',
			'string_translations.translator_id as translator_id',
			'NULL as translate_job_id',
			'core_status.tp_revision AS revision',
			'core_status.ts_status AS ts_status',
			'NULL AS needs_update',
			'NULL AS editor',
			'string_translations.status = ' . ICL_TM_COMPLETE . '  AS has_completed_translation',
			'NULL AS editor_job_id',
			'0 AS automatic',
			'NULL AS review_status',
			'NULL AS trid',
			'NULL AS element_type',
			'NULL AS job_title',
		);

		return $this->build_query( $params, $columns );
	}

	/**
	 * Get count query
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 *
	 * @return int|string
	 */
	public function get_count_query( WPML_TM_Jobs_Search_Params $params ) {
		$columns = array( 'COUNT(string_translations.id)' );

		return $this->build_query( $params, $columns );
	}

	/**
	 * Build query
	 *
	 * @param WPML_TM_Jobs_Search_Params $params  Job search params.
	 * @param array                      $columns Database columns.
	 *
	 * @return string
	 */
	private function build_query( WPML_TM_Jobs_Search_Params $params, array $columns ) {
		if ( $this->check_job_type( $params ) ) {
			return '';
		}

		$query_builder = clone $this->query_builder;
		$query_builder->set_columns( $columns );
		$query_builder->set_from( "{$this->wpdb->prefix}icl_translation_batches AS translation_batches" );

		$this->define_joins( $query_builder );
		$this->define_filters( $query_builder, $params );

		$query_builder->set_limit( $params );
		$query_builder->set_order( $params );

		return $query_builder->build();
	}

	/**
	 * Check job type.
	 *
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 *
	 * @return bool
	 */
	protected function check_job_type( WPML_TM_Jobs_Search_Params $params ) {
		return $params->get_job_types() && ! in_array( WPML_TM_Job_Entity::STRING_TYPE, $params->get_job_types(), true );
	}

	/**
	 * Define joins
	 *
	 * @param QueryBuilder $query_builder Query builder instance.
	 */
	private function define_joins( QueryBuilder $query_builder ) {
		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_string_translations AS string_translations 
				  ON string_translations.batch_id = translation_batches.id"
		);

		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_strings AS strings 
				  ON strings.id = string_translations.string_id"
		);

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_string_status AS string_status 
				  ON string_status.string_translation_id = string_translations.id"
		);

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_core_status AS core_status 
				  ON core_status.rid = string_status.rid"
		);

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_languages source_languages 
				  ON source_languages.code = strings.language"
		);

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_languages target_languages 
				  ON target_languages.code = string_translations.language"
		);

		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_translation_batches batches 
				  ON batches.id = string_translations.batch_id"
		);
	}

	/**
	 * Define filters
	 *
	 * @param QueryBuilder               $query_builder Query builder instance.
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 */
	private function define_filters( QueryBuilder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		$query_builder->set_status_filter( 'string_translations.status', $params );
		$query_builder = $this->set_scope_filter( $query_builder, $params );

		$query_builder->set_multi_value_text_filter( 'strings.value', $params->get_title() );
		$query_builder->set_multi_value_text_filter( $this->batch_name_column, $params->get_batch_name() );
		$query_builder->set_source_language( 'strings.language', $params );
		$query_builder->set_target_language( 'string_translations.language', $params );

		$query_builder->set_translated_by_filter(
			'string_translations.translator_id',
			'string_translations.translation_service',
			$params
		);

		if ( $params->get_sent() ) {
			$query_builder->set_date_range( 'string_status.timestamp', $params->get_sent() );
		}

		$query_builder->set_numeric_value_filter( 'string_translations.id', $params->get_first_local_job_id() );
		$query_builder->set_numeric_value_filter( 'strings.id', $params->get_original_element_id() );
		$query_builder->set_tp_id_filter( 'string_status.rid', $params );

		if ( $params->get_deadline() ) {
			$query_builder->add_AND_where_condition( '1 = 0' );
		}
	}

	private function set_scope_filter( QueryBuilder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		switch ( $params->get_scope() ) {
			case WPML_TM_Jobs_Search_Params::SCOPE_LOCAL:
				$query_builder->add_AND_where_condition( 'string_status.rid IS NULL' );
				break;
			case WPML_TM_Jobs_Search_Params::SCOPE_REMOTE:
				$query_builder->add_AND_where_condition( 'string_status.rid IS NOT NULL' );
				break;
			case WPML_TM_Jobs_Search_Params::SCOPE_ATE:
				/*
				 This class serves the old fashioned string jobs which did not support ATE.
				 Due to that, it should return nothing when ATE Scope is set.
				*/
				$query_builder->add_AND_where_condition( '1 <> 1' );
				break;
		}

		return $query_builder;
	}
}
