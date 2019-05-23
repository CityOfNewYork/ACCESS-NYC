<?php

class WPML_TM_Translatable_Element_Provider {

	/** @var WPML_TM_Word_Count_Records $word_count_records */
	private $word_count_records;

	/** @var WPML_TM_Word_Count_Single_Process $single_process */
	private $single_process;

	/** @var null|WPML_ST_Package_Factory $st_package_factory */
	private $st_package_factory;

	public function __construct(
		WPML_TM_Word_Count_Records $word_count_records,
		WPML_TM_Word_Count_Single_Process $single_process,
		WPML_ST_Package_Factory $st_package_factory = null
	) {
		$this->word_count_records = $word_count_records;
		$this->single_process     = $single_process;
		$this->st_package_factory = $st_package_factory;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return null|WPML_TM_Package_Element|WPML_TM_Post|WPML_TM_String
	 */
	public function get_from_job( WPML_TM_Job_Entity $job ) {
		$id = $job->get_original_element_id();

		switch ( $job->get_type() ) {
			case WPML_TM_Job_Entity::POST_TYPE:
				return $this->get_post( $id );

			case WPML_TM_Job_Entity::STRING_TYPE:
				return $this->get_string( $id );

			case WPML_TM_Job_Entity::PACKAGE_TYPE:
				return $this->get_package( $id );
		}

		return null;
	}

	/**
	 * @param string $type
	 * @param int    $id
	 *
	 * @return null|WPML_TM_Package_Element|WPML_TM_Post|WPML_TM_String
	 */
	public function get_from_type( $type, $id ) {
		switch ( $type ) {
			case 'post':
				return $this->get_post( $id );

			case 'string':
				return $this->get_string( $id );

			case 'package':
				return $this->get_package( $id );
		}

		return null;
	}

	/**
	 * @param int $id
	 *
	 * @return WPML_TM_Post
	 */
	private function get_post( $id ) {
		return new WPML_TM_Post( $id, $this->word_count_records, $this->single_process );
	}

	/**
	 * @param int $id
	 *
	 * @return WPML_TM_String
	 */
	private function get_string( $id ) {
		return new WPML_TM_String( $id, $this->word_count_records, $this->single_process );
	}

	/**
	 * @param int $id
	 *
	 * @return WPML_TM_Package_Element
	 */
	private function get_package( $id ) {
		return new WPML_TM_Package_Element( $id, $this->word_count_records, $this->single_process, $this->st_package_factory );
	}
}
