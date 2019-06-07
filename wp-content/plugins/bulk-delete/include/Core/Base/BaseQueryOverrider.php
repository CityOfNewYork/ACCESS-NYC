<?php

namespace BulkWP\BulkDelete\Core\Base;

/**
 * Query Overrider.
 *
 * Create an instance of this class to create a new query overrider.
 *
 * @since 6.0.1
 */
abstract class BaseQueryOverrider {
	/**
	 * Parse the query object.
	 *
	 * @param \WP_Query $query Query object.
	 */
	abstract public function parse_query( $query );

	/**
	 * Load the Query Overrider.
	 *
	 * The `parse_query` hook is set during loading.
	 */
	public function load() {
		add_action( 'parse_query', array( $this, 'parse_query' ) );
	}
}
