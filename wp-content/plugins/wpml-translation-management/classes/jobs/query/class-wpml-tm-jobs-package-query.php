<?php

class WPML_TM_Jobs_Package_Query extends WPML_TM_Jobs_Post_Query {

	/** @var string */
	protected $type_column = WPML_TM_Job_Entity::PACKAGE_TYPE;

	/** @var string */
	protected $title_column = 'string_packages.title';

	protected function add_resource_join( WPML_TM_Jobs_Query_Builder $query_builder ) {
		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}icl_string_packages string_packages 
			ON string_packages.ID = original_translations.element_id" );

		$query_builder->add_AND_where_condition( 'original_translations.element_type LIKE "package%"' );
	}

	/**
	 * @param WPML_TM_Jobs_Search_Params $params
	 *
	 * @return bool
	 */
	protected function check_job_type( WPML_TM_Jobs_Search_Params $params ) {
		return $params->get_job_types() && ! in_array( WPML_TM_Job_Entity::PACKAGE_TYPE, $params->get_job_types(), true );
	}
}