<?php

namespace WPML\TM\Jobs\Query;

use WPML_TM_Job_Entity;

class PackageQuery extends PostQuery {
	/** @var string */
	protected $title_column = 'string_packages.title';

	protected function add_resource_join( QueryBuilder $query_builder ) {
		$query_builder->add_join(
			"INNER JOIN {$this->wpdb->prefix}icl_string_packages string_packages 
			ON string_packages.ID = original_translations.element_id"
		);

		$query_builder->add_AND_where_condition( "original_translations.element_type LIKE 'package%'" );
	}


	protected function get_type() {
		return WPML_TM_Job_Entity::PACKAGE_TYPE;
	}
}
