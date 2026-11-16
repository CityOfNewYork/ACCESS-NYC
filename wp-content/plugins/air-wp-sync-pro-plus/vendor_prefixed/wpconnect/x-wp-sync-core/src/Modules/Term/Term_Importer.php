<?php
/**
 * Term Importer.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Term;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Class Term_Importer
 */
class Term_Importer extends Abstract_Importer {

	/**
	 * {@inheritDoc}
	 *
	 * @param object     $record Remote record.
	 * @param array      $fields Remote record fields.
	 * @param mixed|null $term_id WordPress object id.
	 *
	 * @throws \Exception From wp_update_term / wp_insert_term.
	 */
	protected function import_record( $record, $fields, $term_id = null ) {

		$term_metas = array(
			'_air_wp_sync_record_id'   => $record->id,
			'_air_wp_sync_hash'        => $this->generate_hash( $record ),
			'_air_wp_sync_importer_id' => $this->infos()->get( 'id' ),
			'_air_wp_sync_updated_at'  => gmdate( 'Y-m-d H:i:s' ),
		);

		/**
		 * Filters import term data that will be used by wp_update_term and wp_insert_term.
		 *
		 * @param array $term_data Term data.
		 * @param Abstract_Importer $importer Importer.
		 * @param array $fields Fields.
		 * @param \stdClass $record Remote record.
		 * @param mixed|null $term_id WordPress term id.
		 */
		$term_data = apply_filters( 'airwpsync/import_term_data', array(), $this, $fields, $record, $term_id );

		// Make sure we have mandatory data.
		if ( empty( $term_data['name'] ) ) {
			throw new \Exception( esc_html__( 'Term name is missing', 'air-wp-sync-pro-plus' ) );
		}

		$taxonomy = ! empty( $term_data['taxonomy'] ) ? $term_data['taxonomy'] : $this->config()->get( 'taxonomy' );

		$term_data = array_filter(
			array_merge(
				$term_data,
				array(
					'slug'        => ! empty( $term_data['slug'] ) ? $term_data['slug'] : '',
					'parent'      => ! empty( $term_data['parent'] ) ? (int) $term_data['parent'] : '',
					'description' => ! empty( $term_data['description'] ) ? $term_data['description'] : '',
					'alias_of'    => ! empty( $term_data['alias_of'] ) ? $term_data['alias_of'] : '',
				)
			)
		);

		// Insert or update post.
		if ( $term_id ) {
			$result = wp_update_term( $term_id, $taxonomy, $term_data );
		} else {
			$result = wp_insert_term( $term_data['name'], $taxonomy, $term_data );
		}

		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_html( $result->get_error_message() ) );
		}

		$term_id = $result['term_id'];

		// Handle metas.
		foreach ( $term_metas as $meta_key => $meta_value ) {
			update_term_meta( $term_id, $meta_key, $meta_value );
		}

		do_action( 'airwpsync/import_record_after', $this, $fields, $record, $term_id );

		return $term_id;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param object $record Remote record.
	 * @param bool   $exclude_other_connections Exclude other connections from the search.
	 *
	 * @return mixed|false
	 */
	protected function get_existing_content_id( $record, $exclude_other_connections = true ) {
		$meta_query = array(
			array(
				'key'   => '_air_wp_sync_record_id',
				'value' => $record->id,
			),
		);
		if ( $exclude_other_connections ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'   => '_air_wp_sync_importer_id',
					'value' => $this->infos()->get( 'id' ),
				),
				array(
					'key'     => '_air_wp_sync_importer_id',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		$terms = get_terms(
			array(
				'fields'     => 'ids',
				'hide_empty' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => $meta_query,
			)
		);

		if ( count( $terms ) === 0 ) {
			return false;
		}

		return array_shift( $terms );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $content_id WordPress object id.
	 */
	protected function get_existing_content_hash( $content_id ) {
		return get_term_meta( $content_id, '_air_wp_sync_hash', true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $run_id Run id.
	 */
	public function delete_removed_contents( $run_id ) {
		$content_ids = $this->get_run_option( $run_id, 'content_ids', array() );
		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}

		$terms = get_terms(
			array(
				'exclude'    => $content_ids,
				'number'     => 0,
				'hide_empty' => false,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_air_wp_sync_importer_id',
						'value' => $this->infos()->get( 'id' ),
					),
				),
			)
		);

		$count_deleted = 0;
		foreach ( $terms as $term ) {
			if ( wp_delete_term( $term->term_id, $term->taxonomy ) ) {
				++$count_deleted;
			}
		}
		update_post_meta( $this->infos()->get( 'id' ), 'count_deleted', $count_deleted );
		return $count_deleted;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $object_id WordPress object id.
	 * @return bool true if the content has been deleted.
	 */
	public function delete_created_content( $object_id ) {
		$term = get_term( $object_id );
		return (bool) ( $term && wp_delete_term( $object_id, $term->taxonomy ) );
	}
}
