<?php
namespace BulkWP\BulkDelete\Core\Terms;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for deleting terms.
 *
 * @since 6.0.0
 */
abstract class TermsModule extends BaseModule {
	/**
	 * Get the list of terms ids that need to be deleted.
	 *
	 * Return an empty query array to short-circuit deletion.
	 *
	 * @param array $options Delete options.
	 *
	 * @return int[] List of term ids to delete.
	 */
	abstract protected function get_term_ids_to_delete( $options );

	protected $item_type = 'terms';

	/**
	 * Handle common filters.
	 *
	 * @param array $request Request array.
	 *
	 * @return array User options.
	 */
	protected function parse_common_filters( $request ) {
		$options = array();

		$options['taxonomy'] = sanitize_text_field( bd_array_get( $request, 'smbd_' . $this->field_slug . '_taxonomy' ) );

		return $options;
	}

	/**
	 * Perform the deletion.
	 *
	 * @param array $options Array of Delete options.
	 *
	 * @return int Number of items that were deleted.
	 */
	protected function do_delete( $options ) {
		$term_ids_to_delete = $this->get_term_ids_to_delete( $options );

		if ( $term_ids_to_delete <= 0 ) {
			// Short circuit deletion, if nothing needs to be deleted.
			return 0;
		}

		return $this->delete_terms_by_id( $term_ids_to_delete, $options );
	}

	/**
	 * Delete terms by ids.
	 *
	 * @param int[] $term_ids List of term ids to delete.
	 * @param array $options  User options.
	 *
	 * @return int Number of terms deleted.
	 */
	protected function delete_terms_by_id( $term_ids, $options ) {
		$count = 0;

		foreach ( $term_ids as $term_id ) {
			if ( wp_delete_term( $term_id, $options['taxonomy'] ) ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Get all terms from a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return \WP_Term[] List of terms.
	 */
	protected function get_all_terms( $taxonomy ) {
		$args = array(
			'taxonomy' => $taxonomy,
			'fields'   => 'all',
		);

		return $this->query_terms( $args );
	}

	/**
	 * Query terms using WP_Term_Query.
	 *
	 * @param array $query Query args.
	 *
	 * @return array List of terms.
	 */
	protected function query_terms( $query ) {
		$defaults = array(
			'fields'                 => 'ids', // retrieve only ids.
			'hide_empty'             => false,
			'count'                  => false,
			'update_term_meta_cache' => false,
		);

		$query = wp_parse_args( $query, $defaults );

		$term_query = new \WP_Term_Query();

		/**
		 * This action runs before the query happens.
		 *
		 * @since 6.0.0
		 *
		 * @param \WP_Term_Query $term_query Query object.
		 */
		do_action( 'bd_before_query', $term_query );

		$terms = $term_query->query( $query );

		/**
		 * This action runs after the query happens.
		 *
		 * @since 6.0.0
		 *
		 * @param \WP_Term_Query $term_query Query object.
		 */
		do_action( 'bd_after_query', $term_query );

		return $terms;
	}

	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of terms deleted */
		return _n( 'Deleted %d term with the selected options', 'Deleted %d terms with the selected options', $items_deleted, 'bulk-delete' );
	}
}
