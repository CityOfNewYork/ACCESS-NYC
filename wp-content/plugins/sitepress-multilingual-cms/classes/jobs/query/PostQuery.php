<?php

namespace WPML\TM\Jobs\Query;

use WPML_TM_Job_Entity;

class PostQuery extends AbstractQuery {
	/**
	 * @param  QueryBuilder  $query_builder
	 */
	protected function add_resource_join( QueryBuilder $query_builder ) {
		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}posts posts ON posts.ID = original_translations.element_id" );

		$query_builder->add_AND_where_condition( "original_translations.element_type LIKE 'post%'" );
	}

	protected function get_type() {
		return WPML_TM_Job_Entity::POST_TYPE;
	}
}
