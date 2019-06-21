<?php

/**
 * @deprecated version 4.3.0   use 'wp-seo-multilingual` plugin instead.
 */
class WPML_Compatibility_Wordpress_Seo_Categories_Old implements IWPML_Action {

	public function add_hooks() {
		add_filter( 'category_rewrite_rules', array( $this, 'append_categories_hook' ), 1, 1 );
		add_filter( 'category_rewrite_rules', array( $this, 'turn_off_get_terms_filter' ), PHP_INT_MAX, 1 );
	}

	public function append_categories_hook( $rules ) {
		add_filter( 'get_terms', array( $this, 'append_categories_translations' ), 10, 2 );

		return $rules;
	}

	public function turn_off_get_terms_filter( $rules ) {
		remove_filter( 'get_terms', array( $this, 'append_categories_translations' ) );

		return $rules;
	}

	public function append_categories_translations( $categories, $taxonomy ) {
		if ( ! in_array( 'category', $taxonomy, true ) || ! $this->is_array_of_wp_term( $categories ) ) {
			return $categories;
		}

		global $wpdb;

		$sql = "
			SELECT t.term_id FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			WHERE tt.taxonomy = 'category'
		";

		return array_filter( array_map( array( $this, 'map_to_term' ), $wpdb->get_col( $sql ) ) );
	}

	/**
	 * @param array $terms
	 *
	 * @return bool
	 */
	private function is_array_of_wp_term( array $terms ) {
		return current( $terms ) instanceof WP_Term;
	}

	/**
	 * @param $term_id
	 *
	 * @return false|WP_Term
	 */
	protected function map_to_term( $term_id ) {
		return get_term_by( 'term_id', $term_id, 'category' );
	}
}