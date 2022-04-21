<?php

namespace WPML\TM\Jobs\Query;

use WPML_TM_Job_Entity;

class StringsBatchQuery extends AbstractQuery {
	/** @var string */
	protected $title_column = 'translation_batches.batch_name';

	protected function add_resource_join( QueryBuilder $query_builder ) {
		$query_builder->add_join( "INNER JOIN {$this->wpdb->prefix}icl_translation_batches translation_batches ON translation_batches.id = original_translations.element_id" );

		$query_builder->add_AND_where_condition( "original_translations.element_type = 'st-batch_strings'" );
	}

	protected function get_type() {
		return WPML_TM_Job_Entity::STRING_BATCH;
	}
}
