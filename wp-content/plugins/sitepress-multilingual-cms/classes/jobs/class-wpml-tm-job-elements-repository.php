<?php

class WPML_TM_Job_Elements_Repository {

	/** @var wpdb */
	private $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Element_Entity[]
	 */
	public function get_job_elements( WPML_TM_Post_Job_Entity $job ) {
		$sql = "
			SELECT translate.*
			FROM {$this->wpdb->prefix}icl_translate translate
			WHERE job_id = %d
		";

		$rowset = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $job->get_translate_job_id() ) );

		return is_array( $rowset )
			? array_map( array( $this, 'build_element_entity' ), $rowset )
			: [];
	}

	/**
	 * @param stdClass $raw_data
	 *
	 * @return WPML_TM_Job_Element_Entity
	 */
	private function build_element_entity( stdClass $raw_data ) {
		return new WPML_TM_Job_Element_Entity(
			$raw_data->tid,
			$raw_data->content_id,
			$raw_data->timestamp,
			$raw_data->field_type,
			$raw_data->field_format,
			$raw_data->field_translate,
			$raw_data->field_data,
			$raw_data->field_data_translated,
			$raw_data->field_finished
		);
	}
}
