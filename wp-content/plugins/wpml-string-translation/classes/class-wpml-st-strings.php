<?php

use WPML\API\Sanitize;

class WPML_ST_Strings {

	const EMPTY_CONTEXT_LABEL = 'empty-context-domain';

	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var WP_Query
	 */
	private $wp_query;
	/**
	 * @var wpdb
	 */
	private $wpdb;

	public function __construct( $sitepress, $wpdb, $wp_query ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
		$this->wp_query  = $wp_query;
	}

	public function get_string_translations() {
		$string_translations = array();

		$extra_cond = '';

		$active_languages = $this->sitepress->get_active_languages();

		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		$status_filter = isset( $_GET['status'] ) ? (int) $_GET['status'] : false;

		$translation_priority = isset( $_GET['translation-priority'] ) ? $_GET['translation-priority'] : false;

		if ( false !== $status_filter ) {
			if ( ICL_TM_COMPLETE === $status_filter ) {
				$extra_cond .= ' AND s.status = ' . ICL_TM_COMPLETE;
			} elseif ( ICL_STRING_TRANSLATION_PARTIAL === $status_filter ) {
				$extra_cond .= ' AND s.status = ' . ICL_STRING_TRANSLATION_PARTIAL;
			} elseif ( ICL_TM_WAITING_FOR_TRANSLATOR !== $status_filter ) {
				$extra_cond .= ' AND s.status IN (' . ICL_STRING_TRANSLATION_PARTIAL . ',' . ICL_TM_NEEDS_UPDATE . ',' . ICL_TM_NOT_TRANSLATED . ',' . ICL_TM_WAITING_FOR_TRANSLATOR . ')';
			}
		}

		if ( false !== $translation_priority ) {
			/** @var string $esc_translation_priority */
			$esc_translation_priority = esc_sql( $translation_priority );
			if ( __( 'Optional', 'sitepress' ) === $translation_priority ) {
				$extra_cond .= " AND s.translation_priority IN ( '" . $esc_translation_priority . "', '' ) ";
			} else {
				$extra_cond .= " AND s.translation_priority = '" . $esc_translation_priority . "' ";
			}
		}

		if ( array_key_exists( 'context', $_GET ) ) {
			$context = stripslashes( html_entity_decode( (string) Sanitize::stringProp( 'context', $_GET ), ENT_QUOTES ) );

			if ( self::EMPTY_CONTEXT_LABEL === $context ) {
				$context = '';
			}
		}

		if ( isset( $context ) ) {
			/** @phpstan-ignore-next-line */
			$extra_cond .= $this->wpdb->prepare( ' AND s.context = %s ', $context );
		}

		if ( $this->must_show_all_results() ) {
			$limit  = 9999;
			$offset = 0;
		} else {
			$limit         = $this->get_strings_per_page();
			$_GET['paged'] = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
			$offset        = ( $_GET['paged'] - 1 ) * $limit;
		}

		$search_filter = $this->get_search_filter();

		$joins     = [];
		$sql_query = ' WHERE 1 ';
		if ( ICL_TM_WAITING_FOR_TRANSLATOR === $status_filter ) {
			$sql_query .= ' AND s.status = ' . ICL_TM_WAITING_FOR_TRANSLATOR;
		} elseif ( $active_languages && $search_filter && ! $this->must_show_all_results() ) {
			$sql_query .= ' AND ' . $this->get_value_search_query();
			$joins[]    = "LEFT JOIN {$this->wpdb->prefix}icl_string_translations str ON str.string_id = s.id";
		}

		if ( $this->is_troubleshooting_filter_enabled() ) {
			// This is a troubleshooting filter, it should display only String Translation elements that are in a wrong state.
			// @see wpmldev-1920 - Strings that are incorrectly duplicated when re-translating a post that was edited using native editor.

			$joins[] = ' LEFT JOIN  ' . $this->wpdb->prefix . 'icl_string_packages sp ON sp.ID = s.string_package_id';
			$joins[] = ' INNER JOIN  ' . $this->wpdb->prefix . 'icl_translate it ON it.field_data_translated = sp.name';
			$sql_query .=' AND it.field_type="original_id"';
		}

		$res = $this->get_results( $sql_query, $extra_cond, $offset, $limit, $joins );

		if ( $res ) {
			$extra_cond = '';
			if ( isset( $_GET['translation_language'] ) ) {
				/** @var string $translation_language */
				$translation_language = esc_sql( $_GET['translation_language'] );
				$extra_cond .= " AND language='" . $translation_language . "'";
			}

			foreach ( $res as $row ) {
				$string_translations[ $row['string_id'] ] = $row;

				// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
				$tr = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"
                    SELECT id, language, status, value, mo_string, translator_id, translation_date  
                    FROM {$this->wpdb->prefix}icl_string_translations 
                    WHERE string_id=%d {$extra_cond}
                ",
						$row['string_id']
					),
					ARRAY_A
				);

				if ( $tr ) {
					foreach ( $tr as $t ) {
						$string_translations[ $row['string_id'] ]['translations'][ $t['language'] ] = $t;
					}
				}
			}
		}

		return WPML\ST\Basket\Status::add( $string_translations, array_keys( $active_languages ) );
	}

	/**
	 * @return string
	 */
	private function get_value_search_query() {
		$language_where = wpml_collect(
			[
				$this->get_original_value_filter_sql(),
				$this->get_name_filter_sql(),
				$this->get_context_filter_sql(),

			]
		);

		$search_context = $this->get_search_context_filter();

		if ( $search_context['translation'] ) {
			$language_where->push( $this->get_translation_value_filter_sql() );
		}
		if ( $search_context['mo_string'] ) {
			$language_where->push( $this->get_mo_file_value_filter_sql() );
		}

		return sprintf( '((%s))', $language_where->implode( ') OR (' ) );
	}

	/**
	 * @return string
	 */
	private function get_original_value_filter_sql() {
		return $this->get_column_filter_sql( 's.value', $this->get_search_filter(), $this->is_exact_match() );
	}

	/**
	 * @return string
	 */
	private function get_name_filter_sql() {
		return $this->get_column_filter_sql( 's.name', $this->get_search_filter(), $this->is_exact_match() );
	}
	/**
	 * @return string
	 */
	private function get_context_filter_sql() {
		return $this->get_column_filter_sql( 's.gettext_context', $this->get_search_filter(), $this->is_exact_match() );
	}

	/**
	 * @return string
	 */
	private function get_translation_value_filter_sql() {
		return $this->get_column_filter_sql( 'str.value', $this->get_search_filter(), $this->is_exact_match() );
	}

	/**
	 * @return string
	 */
	private function get_mo_file_value_filter_sql() {
		return $this->get_column_filter_sql( 'str.mo_string', $this->get_search_filter(), $this->is_exact_match() );
	}

	/**
	 * @param string            $column
	 * @param string|null|false $search_filter
	 * @param bool|null         $exact_match
	 *
	 * @return string
	 */
	private function get_column_filter_sql( $column, $search_filter, $exact_match ) {
		/** @var string $search_filter_html */
		$search_filter_html = esc_html( (string) $search_filter );

		$column = esc_sql( $column );
		$search_filter = esc_sql( (string) $search_filter );
		$search_filter_html = esc_sql( $search_filter_html );

		if ( $search_filter === $search_filter_html ) {
			// No special characters involved.
			return $exact_match
				? "$column = '$search_filter'"
				: "$column LIKE '%$search_filter%'";
		}
		/** @var string $replaced_search_filter */
		$replaced_search_filter = $search_filter ? str_replace( "'", "&#039;", $search_filter ) : '';

		// Special characters involved - search also for HTML version.
		return $exact_match
			? "($column = '$search_filter' OR $column = '$search_filter_html')"
			: "($column LIKE '%$search_filter%' OR $column LIKE '%$search_filter_html%')";
	}

	public function get_per_domain_counts( $status ) {
		$extra_cond = '';

		if ( false !== $status ) {
			if ( ICL_TM_COMPLETE === $status ) {
				$extra_cond .= ' AND s.status = ' . ICL_TM_COMPLETE;
			} else {
				$extra_cond .= ' AND s.status IN (' . ICL_STRING_TRANSLATION_PARTIAL . ',' . ICL_TM_NEEDS_UPDATE . ',' . ICL_TM_NOT_TRANSLATED . ')';
			}
		}

		$results = $this->wpdb->get_results(
			"
        SELECT context, COUNT(context) AS c
        FROM {$this->wpdb->prefix}icl_strings s
        WHERE 1 {$extra_cond} AND TRIM(s.value) <> ''
        GROUP BY context
        ORDER BY context ASC"
		);

		return $results;
	}


	private function get_strings_per_page() {
		$st_settings = $this->sitepress->get_setting( 'st' );

		return isset( $st_settings['strings_per_page'] ) ? $st_settings['strings_per_page'] : WPML_ST_DEFAULT_STRINGS_PER_PAGE;
	}

	private function get_results( $where_snippet, $extra_cond, $offset, $limit, $joins = array(), $selects = array() ) {
		$query  = $this->build_sql_start( $selects, $joins );
		$query .= $where_snippet;
		$query .= " {$extra_cond} ";
		$query .= $this->filter_empty_order_snippet( $offset, $limit );

		$res = $this->wpdb->get_results( $query, ARRAY_A );
		$this->set_pagination_counts( $limit );

		return $res;
	}

	private function filter_empty_order_snippet( $offset, $limit ) {

		return " AND TRIM(s.value) <> '' ORDER BY string_id DESC LIMIT {$offset},{$limit}";
	}

	private function set_pagination_counts( $limit ) {
		if ( ! is_null( $this->wp_query ) ) {
			$this->wp_query->found_posts                  = $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
			$this->wp_query->query_vars['posts_per_page'] = $limit;
			$this->wp_query->max_num_pages                = ceil( $this->wp_query->found_posts / $limit );
		}
	}

	private function build_sql_start( $selects = array(), $joins = array() ) {
		array_unshift( $selects, 'SQL_CALC_FOUND_ROWS DISTINCT(s.id) AS string_id, s.language AS string_language, s.string_package_id, s.context, s.gettext_context, s.name, s.value, s.status AS status, s.translation_priority' );

		return 'SELECT ' . implode( ', ', $selects ) . " FROM {$this->wpdb->prefix}icl_strings s " . implode( PHP_EOL, $joins ) . ' ';
	}

	/**
	 * @return string|false
	 */
	private function get_search_filter() {
		if ( array_key_exists( 'search', $_GET ) ) {
			return stripcslashes( $_GET['search'] );
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function is_exact_match() {
		if ( array_key_exists( 'em', $_GET ) ) {
			return 1 === (int) $_GET['em'];
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function get_search_context_filter() {
		$result = array(
			'original'    => true,
			'translation' => false,
			'mo_string'   => false,
		);
		if ( array_key_exists( 'search_translation', $_GET ) && ! $this->must_show_all_results() ) {
			$result['translation'] = (bool) $_GET['search_translation'];
			$result['mo_string']   = (bool) $_GET['search_translation'];
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function is_troubleshooting_filter_enabled() {
		return array_key_exists( 'troubleshooting', $_GET ) && $_GET['troubleshooting'] === '1';
	}

	/**
	 * @return bool
	 */
	private function must_show_all_results() {
		return isset( $_GET['show_results'] ) && 'all' === $_GET['show_results'];
	}

}
