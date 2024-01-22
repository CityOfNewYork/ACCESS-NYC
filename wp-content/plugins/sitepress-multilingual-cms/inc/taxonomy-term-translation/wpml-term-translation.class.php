<?php

use WPML\FP\Fns;
use WPML\LIB\WP\Cache;

/**
 * @since      3.2
 *
 * Class WPML_Term_Translation
 *
 * Provides APIs for translating taxonomy terms
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Term_Translation extends WPML_Element_Translation {
	const CACHE_MAX_WARMUP_COUNT = 200;
	const CACHE_EXPIRE           = 0;
	const CACHE_GROUP            = 'wpml_term_translation';

	/** @var int */
	protected $cache_max_warmup_count = self::CACHE_MAX_WARMUP_COUNT;

	/**
	 * @return int
	 */
	public function get_cache_max_warmup_count() {
		return $this->cache_max_warmup_count;
	}

	/**
	 * @param int $cache_max_warmup_count
	 *
	 * @return void
	 */
	public function set_cache_max_warmup_count( $cache_max_warmup_count ) {
		$this->cache_max_warmup_count = $cache_max_warmup_count;
	}

	/**
	 * @return void
	 */
	public function add_hooks() {
		// wp_delete_term: Removes a term from the database.
		add_action( 'delete_term', array( $this, 'invalidate_cache' ) );

		// wp_set_object_terms: Creates term and taxonomy relationships.
		add_action( 'set_object_terms', array( $this, 'invalidate_cache' ) );

		// wp_insert_term: Adds a new term to the database.
		// wp_update_term: Updates term based on arguments provided.
		add_action( 'saved_term', array( $this, 'invalidate_cache' ) );

		// wp_remove_object_terms: Removes term(s) associated with a given object.
		add_action( 'deleted_term_relationships', array( $this, 'invalidate_cache' ) );

		// clean_object_term_cache: Removes the taxonomy relationship to terms from the cache.
		add_action( 'clean_object_term_cache', array( $this, 'invalidate_cache' ) );

		// clean_term_cache: Removes all of the term IDs from the cache.
		// Covers:
		// wp_delete_term,
		// wp_insert_term,
		// wp_update_term,
		// wp_update_term_count_now (Updates the amount of terms in taxonomy),
		// _split_shared_term (Creates a new term for a term_taxonomy item that currently shares its term
		// with another term_taxonomy).
		add_action( 'clean_term_cache', array( $this, 'invalidate_cache' ) );
	}

	/**
	 * @return null|string
	 */
	private function get_cache_expire() {
		return self::CACHE_EXPIRE;
	}

	/**
	 * @return void
	 */
	public function invalidate_cache() {
		Cache::flushGroup( self::CACHE_GROUP );
	}

	/**
	 * @param int $term_id
	 *
	 * @return null|string
	 */
	public function lang_code_by_termid( $term_id ) {
		return $this->get_element_lang_code( (int) $this->adjust_ttid_for_term_id( $term_id ) );
	}

	/**
	 * @param array $data
	 */
	private function set_data_to_cache( array $data ) {
		$expire = $this->get_cache_expire();
		foreach ( $data as $row ) {
			Cache::set( self::CACHE_GROUP, 'ttid_by_termid_' . $row['term_id'], $expire, (int) $row['element_id'] );
			Cache::set( self::CACHE_GROUP, 'ttid_by_termidtaxonomy_' . $row['term_id'] . $row['taxonomy'], $expire, (int) $row['element_id'] );
			Cache::set( self::CACHE_GROUP, 'termid_by_ttid_' . $row['element_id'], $expire, (int) $row['term_id'] );
		}
	}

	/**
	 * We need to fetch items one by one only if items count where term_taxonomy_Id != term_id > CACHE_MAX_WARMUP_COUNT.
	 * Otherwise we should just return the original argument instead of fetching one by one.
	 * Because if no result found in the cache - we already have selected all possible rows and there is no sence to subquery for the same data with extra cond.
	 *
	 * @param int $count
	 *
	 * @return boolean
	 */
	private function is_cache_type_select_all_items_in_one_query( $count ) {
		return $count <= $this->get_cache_max_warmup_count();
	}

	/**
	 * Converts term_id into term_taxonomy_id
	 *
	 * @param string|int $term_id
	 *
	 * @return string|int
	 */
	public function adjust_ttid_for_term_id( $term_id ) {
		$count = $this->maybe_warm_term_id_cache();
		if ( 0 === $count ) {
			return $term_id;
		}

		$key_prefix = 'ttid_by_termid_';
		$key        = $key_prefix . $term_id;

		$get_ttid_or_fallback_to_term_id = function() use ( $key_prefix, $key, $term_id, $count ) {
			if ( $this->is_cache_type_select_all_items_in_one_query( $count ) ) {
				return $term_id;
			}

			$data = $this->get_maybe_warm_term_id_cache_data( ' AND tax.term_id = %d', array( $term_id ) );
			if ( count( $data ) ) {
				$this->set_data_to_cache( $data );
			} else {
				// This will prevent from extra sql queries when function is called with the same param.
				Cache::set( self::CACHE_GROUP, $key_prefix . $term_id, $this->get_cache_expire(), $term_id );
			}

			return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $term_id );
		};

		return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $get_ttid_or_fallback_to_term_id );
	}

	/**
	 * Converts term_taxonomy_id into term_id.
	 *
	 * @param string|int $ttid
	 *
	 * @return string|int
	 */
	public function adjust_term_id_for_ttid( $ttid ) {
		$count = $this->maybe_warm_term_id_cache();
		if ( 0 === $count ) {
			return $ttid;
		}

		$key_prefix = 'termid_by_ttid_';
		$key        = $key_prefix . $ttid;

		$get_term_id_or_fallback_to_ttid = function() use ( $key_prefix, $key, $ttid, $count ) {
			if ( $this->is_cache_type_select_all_items_in_one_query( $count ) ) {
				return $ttid;
			}

			$data = $this->get_maybe_warm_term_id_cache_data( ' AND tax.term_taxonomy_id = %d', array( $ttid ) );
			if ( count( $data ) ) {
				$this->set_data_to_cache( $data );
			} else {
				// This will prevent from extra sql queries when function is called with the same param.
				Cache::set( self::CACHE_GROUP, $key_prefix . $ttid, $this->get_cache_expire(), $ttid );
			}

			return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $ttid );
		};

		return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $get_term_id_or_fallback_to_ttid );
	}

	/**
	 * @param int        $term_id
	 * @param string     $lang_code
	 * @param bool|false $original_fallback if true will return the the input term_id in case no translation is found.
	 *
	 * @return null|int
	 */
	public function term_id_in( $term_id, $lang_code, $original_fallback = false ) {

		return $this->adjust_term_id_for_ttid(
			$this->element_id_in( (int) $this->adjust_ttid_for_term_id( $term_id ), $lang_code, $original_fallback )
		);
	}

	/**
	 * @param string|int $term_id
	 * @param string     $taxonomy
	 *
	 * @return string|int|null
	 */
	public function trid_from_tax_and_id( $term_id, $taxonomy ) {
		$count = $this->maybe_warm_term_id_cache();
		if ( 0 === $count ) {
			return $this->get_element_trid( $term_id );
		}

		$key_prefix = 'ttid_by_termidtaxonomy_';
		$key        = $key_prefix . $term_id . $taxonomy;

		$get_term_id = function() use ( $key_prefix, $key, $term_id, $taxonomy, $count ) {
			if ( $this->is_cache_type_select_all_items_in_one_query( $count ) ) {
				return $term_id;
			}

			$data = $this->get_maybe_warm_term_id_cache_data( ' AND tax.term_id = %d AND tax.taxonomy = %s', array( $term_id, $taxonomy ) );
			if ( count( $data ) ) {
				$this->set_data_to_cache( $data );
			} else {
				// This will prevent from extra sql queries when function is called with the same params.
				Cache::set( self::CACHE_GROUP, $key_prefix . $term_id . $taxonomy, $this->get_cache_expire(), $term_id );
			}

			return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $term_id );
		};

		$ttid = Cache::get( self::CACHE_GROUP, $key )->getOrElse( $get_term_id );

		return $this->get_element_trid( $ttid );
	}

	/**
	 * Returns all post types to which a taxonomy is linked.
	 *
	 * @param string $taxonomy
	 *
	 * @return array
	 *
	 * @since 3.2.3
	 */
	public function get_taxonomy_post_types( $taxonomy ) {
		return WPML_WP_Taxonomy::get_linked_post_types( $taxonomy );
	}

	/**
	 * @return string
	 */
	protected function get_element_join() {

		return "
				JOIN {$this->wpdb->term_taxonomy} tax
					ON wpml_translations.element_id = tax.term_taxonomy_id
						AND wpml_translations.element_type = CONCAT('tax_', tax.taxonomy)
		";
	}

	/**
	 * @param string $cols
	 *
	 * @return string
	 */
	protected function get_query_sql( $cols = 'wpml_translations.element_id, tax.term_id, tax.taxonomy' ) {
		$sql  = '';
		$sql .= "SELECT {$cols} FROM {$this->wpdb->prefix}icl_translations wpml_translations" . $this->get_element_join();
		$sql .= " JOIN {$this->wpdb->terms} terms";
		$sql .= ' ON terms.term_id = tax.term_id';
		$sql .= ' WHERE tax.term_id != tax.term_taxonomy_id';

		return $sql;
	}

	/**
	 * @return string
	 */
	protected function get_type_prefix() {
		return 'tax_';
	}

	/**
	 * @return int
	 */
	private function maybe_warm_term_id_cache() {
		$key = 'items_count_for_cache';

		$get_count = function() use ( $key ) {
			$sql   = $this->get_query_sql( 'COUNT(tax.term_id) AS rowsCount' );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$data  = $this->wpdb->get_results( $sql, ARRAY_A );
			$count = (int) $data[0]['rowsCount'];

			Cache::set( self::CACHE_GROUP, $key, $this->get_cache_expire(), $count );

			if ( $count > 0 && $count <= $this->get_cache_max_warmup_count() ) {
				$data = $this->get_maybe_warm_term_id_cache_data();
				$this->set_data_to_cache( $data );
			}

			return $count;
		};

		return Cache::get( self::CACHE_GROUP, $key )->getOrElse( $get_count );
	}

	/**
	 * @param string $where_sql
	 * @param array  $where_sql_params
	 *
	 * @return array $data
	 */
	private function get_maybe_warm_term_id_cache_data( $where_sql = '', $where_sql_params = array() ) {
		$sql = $this->get_query_sql();

		if ( count( $where_sql_params ) > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $this->wpdb->prepare( $sql . $where_sql, $where_sql_params );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$data = $this->wpdb->get_results( $sql, ARRAY_A );

		return $data;
	}

	/**
	 * @param string $term
	 * @param string $slug
	 * @param string $taxonomy
	 * @param string $lang_code
	 *
	 * @return string
	 */
	public function generate_unique_term_slug( $term, $slug, $taxonomy, $lang_code ) {
		if ( '' === trim( $slug ) ) {
			$slug = sanitize_title( $term );
		}
		return WPML_Terms_Translations::term_unique_slug( $slug, $taxonomy, $lang_code );
	}

	/**
	 * @return self
	 */
	public static function getGlobalInstance() {
		global $wpml_term_translations, $wpdb;

		if ( ! isset( $wpml_term_translations ) ) {
			$wpml_term_translations = new WPML_Term_Translation( $wpdb );
		}

		return $wpml_term_translations;
	}
}
