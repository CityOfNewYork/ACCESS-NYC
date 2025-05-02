<?php

namespace WPML\TM\Jobs\Query;

use wpdb;
use WPML_TM_Editors;
use WPML_TM_Jobs_Search_Params;
use WPML\TM\ATE\Jobs;

abstract class AbstractQuery implements Query {
	/** @var wpdb */
	protected $wpdb;

	/** @var QueryBuilder */
	protected $query_builder;

	/** @var string */
	protected $title_column = 'posts.post_title';

	/** @var string */
	protected $batch_name_column = 'batches.batch_name';

	/**
	 * @param wpdb         $wpdb
	 * @param QueryBuilder $query_builder
	 */
	public function __construct( wpdb $wpdb, QueryBuilder $query_builder ) {
		$this->wpdb          = $wpdb;
		$this->query_builder = $query_builder;
	}


	public function get_data_query( WPML_TM_Jobs_Search_Params $params ) {
		$has_completed_translation_subquery = "
				SELECT COUNT(job_id)
				FROM {$this->wpdb->prefix}icl_translate_job as copmpleted_translation_job
				WHERE copmpleted_translation_job.rid = translation_status.rid AND copmpleted_translation_job.translated = 1
		";

		if ( $params->get_columns_to_select() ) {
			$columns = $params->get_columns_to_select();
		} else {
			$columns = [
				'translation_status.rid AS id',
				"'" . $this->get_type() . "' AS type",
				'translation_status.tp_id AS tp_id',
				'batches.id AS local_batch_id',
				'batches.tp_id AS tp_batch_id',
				$this->batch_name_column,
				'translation_status.status AS status',
				'original_translations.element_id AS original_element_id',
				'translations.source_language_code AS source_language',
				'translations.language_code AS target_language',
				'translations.trid',
				'translations.element_type',
				'translations.element_id',
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
				"({$has_completed_translation_subquery}) > 0 AS has_completed_translation",
				'translate_job.editor_job_id AS editor_job_id',
				'translate_job.automatic AS automatic',
				'translate_job.title AS job_title',
				'translation_status.review_status AS review_status',
			];
		}

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
		return $params->get_job_types() && ! in_array( $this->get_type(), $params->get_job_types(), true );
	}

	/**
	 * @return string
	 */
	abstract protected function get_type();

	protected function define_joins( QueryBuilder $query_builder ) {
		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_translations translations
				ON translations.translation_id = translation_status.translation_id"
		);

		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_translations original_translations
				ON original_translations.trid = translations.trid AND original_translations.language_code = translations.source_language_code"
		);

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

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_languages source_languages
				ON source_languages.code = translations.source_language_code"
		);

		$query_builder->add_join(
			"LEFT JOIN {$this->wpdb->prefix}icl_languages target_languages
				ON target_languages.code = translations.language_code"
		);

		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_translation_batches batches
				ON batches.id = translation_status.batch_id"
		);
	}

	abstract protected function add_resource_join( QueryBuilder $query_builder );

	protected function define_filters( QueryBuilder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		$this->set_status_filter( $query_builder, $params );
		$query_builder = $this->set_scope_filter( $query_builder, $params );
		$query_builder->set_multi_value_text_filter( $this->title_column, $params->get_title() );
		$query_builder->set_multi_value_text_filter( $this->batch_name_column, $params->get_batch_name() );
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

		$query_builder->set_numeric_value_filter( 'translation_status.rid', $params->get_id() );
		$query_builder->set_numeric_value_filter( 'translation_status.rid', $params->get_ids() );
		$query_builder->set_numeric_value_filter( 'translation_status.rid', $params->get_local_job_ids() );
		$query_builder->set_numeric_value_filter(
			'original_translations.element_id',
			$params->get_original_element_id()
		);
		$query_builder->set_tp_id_filter( 'translation_status.tp_id', $params );

		if ( $params->needs_review() ) {
			$query_builder->set_needs_review();
		}

		if ( $params->should_exclude_manual() ) {
			$query_builder->set_automatic();
		}

		if ( $params->should_exclude_longstanding() ) {
			$query_builder->set_max_ate_sync_count( Jobs::LONGSTANDING_AT_ATE_SYNC_COUNT );
		}

		if ( $params->get_max_ate_retries() ) {
			$query_builder->set_max_retries( $params->get_max_ate_retries() );
		}

		if ( $params->exclude_hidden_jobs() ) {
			$query_builder->set_set_excluded_jobs();
		}

		if ( $params->get_element_type() ) {
			$query_builder->set_element_type( $params->get_element_type() );
		}

		if ( $params->get_custom_where_conditions() ) {
			foreach ( $params->get_custom_where_conditions() as $whereCondition ) {
				$query_builder->add_AND_where_condition( $whereCondition );
			}
		}
	}

	private function set_status_filter(
		QueryBuilder $query_builder,
		WPML_TM_Jobs_Search_Params $params
	) {
		if ( $params->get_needs_update() ) {
			$statuses = array_diff( $params->get_status(), [ ICL_TM_NEEDS_UPDATE ] );

			if ( $params->get_needs_update()->is_needs_update_excluded() ) {
				$query_builder->add_AND_where_condition( 'translation_status.needs_update != 1' );
				if ( $statuses ) {
					$query_builder->set_status_filter( 'translation_status.status', $params );
				}
			} else {
				if ( $statuses ) {
					$statuses = wpml_prepare_in( $params->get_status(), '%d' );
					$statuses = sprintf( 'translation_status.status IN (%s)', $statuses );
					$query_builder->add_AND_where_condition( "( translation_status.needs_update = 1 AND {$statuses} )" );
				} else {
					$query_builder->add_AND_where_condition( 'translation_status.needs_update = 1' );
				}
			}
		} else {
			$query_builder->set_status_filter( 'translation_status.status', $params );
		}

		if ( $params->should_exclude_cancelled() ) {
			$query_builder->add_AND_where_condition( 'translation_status.status != 0' );
		}
	}

	private function set_scope_filter( QueryBuilder $query_builder, WPML_TM_Jobs_Search_Params $params ) {
		switch ( $params->get_scope() ) {
			case WPML_TM_Jobs_Search_Params::SCOPE_LOCAL:
				$query_builder->add_AND_where_condition( "translation_status.translation_service = 'local'" );
				break;
			case WPML_TM_Jobs_Search_Params::SCOPE_REMOTE:
				$query_builder->add_AND_where_condition( "translation_status.translation_service != 'local'" );
				break;
			case WPML_TM_Jobs_Search_Params::SCOPE_ATE:
				$query_builder->add_AND_where_condition( "translation_status.translation_service = 'local'" );
				$query_builder->add_AND_where_condition( $this->wpdb->prepare( 'translate_job.editor = %s', WPML_TM_Editors::ATE ) );
				break;
		}

		return $query_builder;
	}
}
