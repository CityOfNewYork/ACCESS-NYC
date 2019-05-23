<?php

class WPML_TM_Jobs_Post_Query implements WPML_TM_Jobs_Query {
	/** @var wpdb */
	protected $wpdb;

	/** @var WPML_TM_Jobs_Query_Builder */
	protected $query_builder;

	/** @var string */
	protected $type_column = WPML_TM_Job_Entity::POST_TYPE;

	/** @var string */
	protected $title_column = 'posts.post_title';

	/**
	 * @param wpdb                       $wpdb
	 * @param WPML_TM_Jobs_Query_Builder $query_builder
	 */
	public function __construct( wpdb $wpdb, WPML_TM_Jobs_Query_Builder $query_builder ) {
		$this->wpdb          = $wpdb;
		$this->query_builder = $query_builder;
	}


	public function get_data_query( WPML_TM_Jobs_Search_Params $params ) {
		$columns = array(
			'translation_status.rid AS id',
			"'" . $this->type_column . "' AS type",
			'translation_status.tp_id AS tp_id',
			'batches.id AS local_batch_id',
			'batches.tp_id AS tp_batch_id',
			'translation_status.status AS status',
			'original_translations.element_id AS original_element_id',
			'translations.source_language_code AS source_language',
			'translations.language_code AS target_language',
			'translation_status.translation_service AS translation_service',
			'translation_status.timestamp AS sent_date',
			'translate_job.deadline_date AS deadline_date',
			'translate_job.completed_date AS completed_date',
			"{$this->title_column} AS title",
			'source_languages.english_name AS source_language_name',
			'target_languages.english_name AS target_language_name',
			'translate_job.translator_id AS translator_id',
			'translate_job.job_id AS translate_job_id',
			'translation_status.tp_revision AS revision',
			'translation_status.ts_status AS ts_status',
			'translation_status.needs_update AS needs_update',
			'translate_job.editor AS editor',
		);

		return $this->build_query( $params, $columns );
	}

	public function get_count_query( WPML_TM_Jobs_Search_Params $params ) {
		$columns = array( 'COUNT(translation_status.rid)' );

		return $this->build_query( $params, $columns );
	}


	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 * @param array                      $columns
	 *
	 * @return string
	 */
	protected function build_query( WPML_TM_Jobs_Search_Params $params, array $columns ) {
		if ( $this->check_job_type( $params ) ) {
			return '';
		}

		$query_builder = clone $this->query_builder;
		$query_builder->set_columns( $columns );
		$query_builder->set_from( "{$this->wpdb->prefix}icl_translation_status translation_status" );

		$this->define_joins( $query_builder );
		$this->define_filters( $query_builder, $params );

		$query_builder->set_limit( $params );
		$query_builder->set_order( $params );

		return $query_builder->build();
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return bool
	 */
	protected function check_job_type( WPML_TM_Jobs_Search_Params $params ) {
		return $params->get_job_types() && ! in_array( WPML_TM_Job_Entity::POST_TYPE, $params->get_job_types(), true );
	}

	protected function define_joins( WPML_TM_Jobs_Query_Builder $query_builder ) {
		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}icl_translations translations 
				ON translations.translation_id = translation_status.translation_id" );

		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}icl_translations original_translations 
				ON original_translations.trid = translations.trid AND original_translations.language_code = translations.source_language_code" );

		$subquery = "
			SELECT *
            FROM {$this->wpdb->prefix}icl_translate_job as translate_job
            WHERE job_id = (
				SELECT MAX(job_id) AS job_id
				FROM {$this->wpdb->prefix}icl_translate_job as sub_translate_job
				WHERE sub_translate_job.rid = translate_job.rid
			)
		";
		$query_builder->add_join( "INNER JOIN ({$subquery}) AS translate_job ON translate_job.rid = translation_status.rid" );

		$this->add_resource_join( $query_builder );

		$query_builder->add_join( "LEFT JOIN {$this->wpdb->prefix}icl_languages source_languages 
				ON source_languages.code = translations.source_language_code" );

		$query_builder->add_join( "LEFT JOIN {$this->wpdb->prefix}icl_languages target_languages 
				ON target_languages.code = translations.language_code" );

		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}icl_translation_batches batches 
				ON batches.id = translation_status.batch_id" );
	}

	protected function add_resource_join( WPML_TM_Jobs_Query_Builder $query_builder ) {
		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}posts posts ON posts.ID = original_translations.element_id" );

		$query_builder->add_AND_where_condition( 'original_translations.element_type LIKE "post%"' );
	}

	protected function define_filters( WPML_TM_Jobs_Query_Builder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		$query_builder->set_status_filter( 'translation_status.status', $params );
		$query_builder->set_scope_filter(
			"translation_status.translation_service = 'local'",
			"translation_status.translation_service != 'local'",
			$params
		);
		$query_builder->set_title_filter( $this->title_column, $params );
		$query_builder->set_source_language( 'translations.source_language_code', $params );
		$query_builder->set_target_language( 'translations.language_code', $params );

		$query_builder->set_translated_by_filter(
			'translate_job.translator_id',
			'translation_status.translation_service',
			$params
		);

		if ( $params->get_sent() ) {
			$query_builder->set_date_range( 'translation_status.timestamp', $params->get_sent() );
		}

		if ( $params->get_deadline() ) {
			$query_builder->set_date_range( 'translate_job.deadline_date', $params->get_deadline() );
		}

		if ( $params->get_completed_date() ) {
			$query_builder->set_date_range( 'translate_job.completed_date', $params->get_completed_date() );
		}

		$query_builder->set_numeric_value_filter( 'translation_status.rid', $params->get_local_job_id() );
		$query_builder->set_numeric_value_filter(
			'original_translations.element_id',
			$params->get_original_element_id()
		);
		$query_builder->set_tp_id_filter( 'translation_status.tp_id', $params );
	}
}