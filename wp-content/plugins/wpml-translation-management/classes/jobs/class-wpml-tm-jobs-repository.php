<?php

class WPML_TM_Jobs_Repository {
	/** @var wpdb */
	private $wpdb;

	/** @var WPML_TM_Jobs_Query */
	private $query_builder;

	/** @var WPML_TM_Job_Elements_Repository */
	private $elements_repository;

	/**
	 * @param wpdb                            $wpdb
	 * @param WPML_TM_Jobs_Query              $query_builder
	 * @param WPML_TM_Job_Elements_Repository $elements_repository
	 */
	public function __construct(
		wpdb $wpdb,
		WPML_TM_Jobs_Query $query_builder,
		WPML_TM_Job_Elements_Repository $elements_repository
	) {
		$this->wpdb                = $wpdb;
		$this->query_builder       = $query_builder;
		$this->elements_repository = $elements_repository;
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	public function get( WPML_TM_Jobs_Search_Params $params ) {
		return new WPML_TM_Jobs_Collection( array_map(
			array( $this, 'build_job_entity' ),
			$this->wpdb->get_results( $this->query_builder->get_data_query( $params ) )
		) );
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return int
	 */
	public function get_count( WPML_TM_Jobs_Search_Params $params ) {
		return (int) $this->wpdb->get_var( $this->query_builder->get_count_query( $params ) );
	}

	/**
	 * @param int    $local_job_id
	 * @param string $job_type
	 *
	 * @throws InvalidArgumentException
	 * @return WPML_TM_Job_Entity|false
	 */
	public function get_job( $local_job_id, $job_type ) {
		$params = new WPML_TM_Jobs_Search_Params();
		$params->set_local_job_id( $local_job_id );
		$params->set_job_types( $job_type );

		$data = $this->wpdb->get_row( $this->query_builder->get_data_query( $params ) );
		if ( $data ) {
			$data = $this->build_job_entity( $data );
		}

		return $data;
	}

	/**
	 * @param stdClass $raw_data
	 *
	 * @return WPML_TM_Job_Entity
	 */
	private function build_job_entity( stdClass $raw_data ) {
		$types = array( WPML_TM_Job_Entity::POST_TYPE, WPML_TM_Job_Entity::PACKAGE_TYPE );
		if ( in_array( $raw_data->type, $types, true ) ) {
			$job = new WPML_TM_Post_Job_Entity(
				$raw_data->id,
				$raw_data->type,
				$raw_data->tp_id,
				new WPML_TM_Jobs_Batch( $raw_data->local_batch_id, $raw_data->tp_batch_id ),
				$raw_data->needs_update ? ICL_TM_NEEDS_UPDATE : (int) $raw_data->status,
				array( $this->elements_repository, 'get_job_elements' )
			);
			$job->set_translate_job_id( $raw_data->translate_job_id );
			$job->set_editor( $raw_data->editor );
			$job->set_completed_date( $raw_data->completed_date ? new DateTime( $raw_data->completed_date ) : null );
		} else {
			$job = new WPML_TM_Job_Entity(
				$raw_data->id,
				$raw_data->type,
				$raw_data->tp_id,
				new WPML_TM_Jobs_Batch( $raw_data->local_batch_id, $raw_data->tp_batch_id ),
				(int) $raw_data->status
			);
		}

		$job->set_original_element_id( $raw_data->original_element_id );
		$job->set_source_language( $raw_data->source_language );
		$job->set_target_language( $raw_data->target_language );
		$job->set_translation_service( $raw_data->translation_service );
		$job->set_sent_date( new DateTime( $raw_data->sent_date ) );
		$job->set_deadline( $this->get_deadline( $raw_data ) );
		$job->set_translator_id( $raw_data->translator_id );
		$job->set_revision( $raw_data->revision );
		$job->set_ts_status( $raw_data->ts_status );

		return $job;
	}

	/**
	 * @param stdClass $raw_data
	 *
	 * @return DateTime|null
	 */
	private function get_deadline( stdClass $raw_data ) {
		if ( $raw_data->deadline_date && '0000-00-00 00:00:00' !== $raw_data->deadline_date ) {
			return new DateTime( $raw_data->deadline_date );
		}

		return null;
	}
}