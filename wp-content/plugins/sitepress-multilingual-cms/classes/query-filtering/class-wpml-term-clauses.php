<?php

/**
 * Class WPML_Term_Clauses
 */
class WPML_Term_Clauses {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_Display_As_Translated_Taxonomy_Query $display_as_translated_query */
	private $display_as_translated_query;

	/** @var WPML_Debug_BackTrace $debug_backtrace */
	private $debug_backtrace;

	/** @var array  */
	private $cache = null;

	/**
	 * WPML_Term_Clauses constructor.
	 *
	 * @param SitePress                                 $sitepress
	 * @param wpdb                                      $wpdb
	 * @param WPML_Display_As_Translated_Taxonomy_Query $display_as_translated_query
	 * @param WPML_Debug_BackTrace                      $debug_backtrace
	 */
	public function __construct(
		SitePress $sitepress,
		wpdb $wpdb,
		WPML_Display_As_Translated_Taxonomy_Query $display_as_translated_query,
		WPML_Debug_BackTrace $debug_backtrace
	) {
		$this->sitepress                   = $sitepress;
		$this->wpdb                        = $wpdb;
		$this->display_as_translated_query = $display_as_translated_query;
		$this->debug_backtrace             = $debug_backtrace;
	}

	/**
	 * @param array $clauses
	 * @param array $taxonomies
	 * @param array $args
	 *
	 * @return array
	 */
	public function filter( $clauses, $taxonomies, $args ) {
		// Special case for when term hierarchy is cached in wp_options.
		if (
			! $taxonomies
			|| $this->debug_backtrace->are_functions_in_call_stack(
				[
					'_get_term_hierarchy',
					[ 'WPML_Term_Translation_Utils', 'synchronize_terms' ],
					'wp_get_object_terms',
					'get_term_by',
				]
			)
		) {
			return $clauses;
		}

		$icl_taxonomies = array();
		foreach ( $taxonomies as $tax ) {
			if ( $this->sitepress->is_translated_taxonomy( $tax ) ) {
				$icl_taxonomies[] = $tax;
			}
		}

		if ( ! $icl_taxonomies ) {
			return $clauses;
		}

		$icl_taxonomies = "'tax_" . join( "','tax_", esc_sql( $icl_taxonomies ) ) . "'";

		$where_lang = $this->get_where_lang();

		$clauses['join'] .= " LEFT JOIN {$this->wpdb->prefix}icl_translations icl_t
                                    ON icl_t.element_id = tt.term_taxonomy_id
                                        AND icl_t.element_type IN ({$icl_taxonomies})";

		$clauses           = $this->maybe_apply_count_adjustment( $clauses );
		$clauses['where'] .= " AND ( ( icl_t.element_type IN ({$icl_taxonomies}) {$where_lang} )
                                    OR icl_t.element_type NOT IN ({$icl_taxonomies}) OR icl_t.element_type IS NULL ) ";

		return $clauses;

	}

	/**
	 * @return string|void
	 */
	private function get_where_lang() {
		$lang = $this->sitepress->get_current_language();
		if ( 'all' === $lang ) {
			return '';
		} else {
			$display_as_translated_snippet = $this->get_display_as_translated_snippet( $lang, $this->sitepress->get_default_language() );
			return $this->wpdb->prepare( " AND ( icl_t.language_code = %s OR {$display_as_translated_snippet} ) ", $lang );
		}
	}

	/**
	 * @param array $clauses
	 *
	 * @return array
	 */
	private function maybe_apply_count_adjustment( $clauses ) {
		if ( $this->should_apply_display_as_translated_adjustments() ) {
			return $this->display_as_translated_query->update_count(
				$clauses,
				$this->sitepress->get_default_language()
			);
		}

		return $clauses;
	}

	/**
	 * @param string $current_language
	 * @param string $fallback_language
	 *
	 * @return string
	 */
	private function get_display_as_translated_snippet( $current_language, $fallback_language ) {
		if ( $this->should_apply_display_as_translated_adjustments() ) {
			return $this->display_as_translated_query->get_language_snippet(
				$current_language,
				$fallback_language,
				$this->get_display_as_translated_taxonomies()
			);
		}

		return '0';
	}

	/**
	 * @return bool
	 */
	private function should_apply_display_as_translated_adjustments() {
		return $this->get_display_as_translated_taxonomies() && ( ! is_admin() || WPML_Ajax::is_frontend_ajax_request() );
	}

	/**
	 * @return array
	 */
	private function get_display_as_translated_taxonomies() {
		if ( $this->cache === null ) {
			$this->cache = $this->sitepress->get_display_as_translated_taxonomies();
		}

		return $this->cache;
	}
}
