<?php
/**
 * WPML_TM_Jobs_String_Query class file
 *
 * @package wpml-translation-management
 */

/**
 * Class WPML_TM_Jobs_String_Query
 */
class WPML_TM_Jobs_String_Query implements WPML_TM_Jobs_Query {

	/**
	 * WP database instance
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Query builder instance
	 *
	 * @var WPML_TM_Jobs_Query_Builder
	 */
	protected $query_builder;

	/**
	 * WPML_TM_Jobs_String_Query constructor.
	 *
	 * @param wpdb                       $wpdb          WP database instance.
	 * @param WPML_TM_Jobs_Query_Builder $query_builder Query builder instance.
	 */
	public function __construct( wpdb $wpdb, WPML_TM_Jobs_Query_Builder $query_builder ) {
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
	 * @param WPML_TM_Jobs_Query_Builder $query_builder Query builder instance.
	 */
	private function define_joins( WPML_TM_Jobs_Query_Builder $query_builder ) {
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
	 * @param WPML_TM_Jobs_Query_Builder $query_builder Query builder instance.
	 * @param WPML_TM_Jobs_Search_Params $params Job search params.
	 */
	private function define_filters( WPML_TM_Jobs_Query_Builder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		$query_builder->set_status_filter( 'string_translations.status', $params );
		$query_builder->set_scope_filter(
			'string_status.rid IS NULL',
			'string_status.rid IS NOT NULL',
			$params
		);
		$query_builder->set_title_filter( 'strings.value', $params );
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

		$query_builder->set_numeric_value_filter( 'string_translations.id', $params->get_local_job_id() );
		$query_builder->set_numeric_value_filter( 'strings.id', $params->get_original_element_id() );
		$query_builder->set_tp_id_filter( 'string_status.rid', $params );
	}
}
