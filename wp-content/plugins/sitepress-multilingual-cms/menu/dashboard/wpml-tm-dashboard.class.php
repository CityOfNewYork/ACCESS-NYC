<?php

use WPML\FP\Lst;

/**
 * Class WPML_TM_Dashboard
 */
class WPML_TM_Dashboard {

	/**
	 * @var array
	 */
	private $translatable_post_types = null;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var int
	 */
	private $found_documents = 0;

	/**
	 * WPML_TM_Dashboard constructor.
	 *
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
		add_filter( 'posts_where', array( $this, 'add_dashboard_filter_conditions' ), 10, 2 );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_documents( $args = array() ) {
		$results   = array();
		$documents = array();

		$defaults = array(
			'from_lang'   => 'en',
			'to_lang'     => '',
			'tstatus'     => -1,
			'sort_by'     => 'date',
			'sort_order'  => 'DESC',
			'limit_no'    => ICL_TM_DOCS_PER_PAGE,
			'parent_type' => 'any',
			'parent_id'   => false,
			'type'        => '',
			'title'       => '',
			'status'      => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),
			'page'        => 0,
		);

		$args = $this->remove_empty_arguments( $args );
		$args = wp_parse_args( $args, $defaults );

		$documents                  = $this->add_string_packages( $documents, $args );
		$documents                  = $this->add_translatable_posts( $documents, $args );
		$filtered_documents         = apply_filters( 'wpml_tm_dashboard_documents', $documents );
		$filtered_documents         = array_slice( $filtered_documents, 0, $args['limit_no'] );
		$results['documents']       = $filtered_documents;
		$results['found_documents'] = $this->found_documents - ( count( $documents ) - count( $filtered_documents ) );

		return $results;
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	private function remove_empty_arguments( $args ) {
		$output = array();
		foreach ( $args as $argument_name => $argument_value ) {
			if ( '' !== $argument_value && null !== $argument_value ) {
				$output[ $argument_name ] = $argument_value;
			}
		}

		return $output;
	}

	/**
	 * Add list of translatable post types to dashboard.
	 *
	 * @param array $results
	 * @param array $args
	 *
	 * @return array
	 */
	private function add_translatable_posts( $results, $args ) {
		$post_types = $this->get_translatable_post_types();
		$offset     = 0;
		if ( $this->is_cpt_type( $args ) ) {
			$post_types = array( $args['type'] );
			$offset     = $args['page'] * $args['limit_no'];
		} elseif ( ! empty( $args['type'] ) ) {
			return $results;
		}

		$query_args = [
			'post_type'               => $post_types,
			'orderby'                 => $args['sort_by'],
			'order'                   => $args['sort_order'],
			'posts_per_page'          => $args['limit_no'] + 1,
			'post_status'             => $args['status'],
			'post_language'           => $args['from_lang'],
			'post_language_to'        => $args['to_lang'],
			'post_translation_status' => $args['tstatus'],
			'suppress_filters'        => false,
			'update_post_meta_cache'  => false,
			'update_post_term_cache'  => false,
			'no_found_rows'           => true,
			'offset'                  => $offset,
		];

		if ( 'any' !== $args['parent_type'] ) {
			switch ( $args['parent_type'] ) {
				case 'page':
					$query_args['post_parent'] = (int) $args['parent_id'];
					break;
				default:
					$query_args['tax_query'] = array(
						array(
							'taxonomy' => $args['parent_type'],
							'field'    => 'term_id',
							'terms'    => (int) $args['parent_id'],
						),
					);
					break;
			}
		}

		if ( isset( $args['translation_priority'] ) ) {

			$translation_priorities = new WPML_TM_Translation_Priorities();

			if ( $translation_priorities->get_default_value_id() === (int) $args['translation_priority'] ) {
				$tax_query = array(
					'relation' => 'OR',
					array(
						'taxonomy' => 'translation_priority',
						'operator' => 'NOT EXISTS',
					),
				);
			}

			$tax_query[] = array(
				'taxonomy' => 'translation_priority',
				'field'    => 'term_id',
				'terms'    => $args['translation_priority'],
			);

			$query_args['tax_query'] = $tax_query;

		}

		if ( ! empty( $args['title'] ) ) {
			$query_args['post_title_like'] = $args['title'];
		}

		$lang = $this->sitepress->get_admin_language();
		$this->sitepress->switch_lang( $args['from_lang'] );
		$query_args = apply_filters( 'wpml_tm_dashboard_post_query_args', $query_args, $args );
		$query      = new WPML_TM_WP_Query( $query_args );
		$this->sitepress->switch_lang( $lang );
		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post ) {
				$language_details                   = $this->sitepress->get_element_language_details( $post->ID, 'post_' . $post->post_type );
				$post_obj                           = new stdClass();
				$post_obj->ID                       = $post->ID;
				$post_obj->translation_element_type = 'post_' . $post->post_type;
				$post_obj->title                    = $post->post_title;
				$post_obj->is_translation           = ( null === $language_details->source_language_code ) ? '0' : '1';
				$post_obj->language_code            = $language_details->language_code;
				$post_obj->trid                     = $language_details->trid;
				$results[]                          = $post_obj;
			}
		}
		$this->found_documents += $query->get_found_count();
		wp_reset_query();
		return $results;
	}

	/**
	 * Add additional where conditions to support the following query arguments:
	 *  - post_title_like         - Allow query posts with SQL LIKE in post title.
	 *  - post_language_to        - Allow query posts with language they are translated to.
	 *  - post_translation_status - Allow to query posts by their translation status.
	 *
	 * @param string $where
	 * @param object $wp_query
	 *
	 * @return string
	 */
	public function add_dashboard_filter_conditions( $where, $wp_query ) {
		$post_title_like         = $wp_query->get( 'post_title_like' );
		$post_language           = $wp_query->get( 'post_language_to' );
		$post_translation_status = (int) $wp_query->get( 'post_translation_status' );

		if ( $post_title_like ) {
			$where .= $this->wpdb->prepare( " AND {$this->wpdb->posts}.post_title LIKE '%s'", '%' . $this->wpdb->esc_like( $post_title_like ) . '%' );
		}

		$post_type = $wp_query->get( 'post_type' );
		if ( Lst::includes( $post_type[0], $this->get_translatable_post_types() ) ) {
			$where .= $this->build_translation_status_where( $post_translation_status, $post_language );
		}

		return $where;
	}

	/**
	 * Add string packages to translation dashboard.
	 *
	 * @param array $results
	 * @param array $args
	 *
	 * @return array
	 */
	private function add_string_packages( $results, $args ) {
		$string_packages_table = $this->wpdb->prefix . 'icl_string_packages';
		$translations_table    = $this->wpdb->prefix . 'icl_translations';
		$offset                = 0;

		if ( $this->is_cpt_type( $args ) ) {
			return array();
		}

		$sql_calc_found_rows = '';
		$must_count_rows     = array_key_exists( 'type', $args ) && ! empty( $args['type'] );
		if ( $must_count_rows ) {
			$offset              = $args['page'] * $args['limit_no'];
			$sql_calc_found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		if ( ! is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
			return $results;
		}

		// Exit if *icl_string_packages table doesn't exist.
		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '$string_packages_table'" ) !== $string_packages_table ) {
			return $results;
		}

		$where = $this->create_string_packages_where( $args );

		$sql      = "SELECT DISTINCT {$sql_calc_found_rows}
				 st_table.ID, 
				 st_table.kind_slug, 
				 st_table.title, 
				 wpml_translations.element_type, 
				 wpml_translations.language_code, 
				 wpml_translations.source_language_code,
				 wpml_translations.trid 
				 FROM {$string_packages_table} AS st_table
				 LEFT JOIN {$translations_table} AS wpml_translations 
				 ON wpml_translations.element_id=st_table.ID OR wpml_translations.element_id = null 
				 WHERE 1 = 1 {$where} 
				 GROUP BY st_table.ID
				 ORDER BY st_table.ID ASC 
				 LIMIT {$args['limit_no']} 
				 OFFSET {$offset}";
		$sql      = apply_filters( 'wpml_tm_dashboard_external_type_sql_query', $sql, $args );
		$packages = $this->wpdb->get_results( $sql );

		if ( $must_count_rows ) {
			$this->found_documents += $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );
		}
		foreach ( $packages as $package ) {
			$package_obj                           = new stdClass();
			$package_obj->ID                       = $package->ID;
			$package_obj->translation_element_type = WPML_Package_Translation::get_package_element_type( $package->kind_slug );
			$package_obj->title                    = $package->title;
			$package_obj->is_translation           = ( null === $package->source_language_code ) ? '0' : '1';
			$package_obj->language_code            = $package->language_code;
			$package_obj->trid                     = $package->trid;
			$results[]                             = $package_obj;
		}

		return $results;
	}

	/**
	 * Create additional where clause for querying string packages based on filters.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	private function create_string_packages_where( $args ) {
		$where = " AND wpml_translations.element_type LIKE 'package%' AND st_table.post_id IS NULL";
		if ( ! $this->is_cpt_type( $args ) && ! empty( $args['type'] ) ) {
			$where .= $this->wpdb->prepare( " AND kind_slug='%s'", $args['type'] );
		}

		if ( ! empty( $args['title'] ) ) {
			$where .= $this->wpdb->prepare( " AND title LIKE '%s'", '%' . $this->wpdb->esc_like( $args['title'] ) . '%' );
		}

		if ( ! empty( $args['to_lang'] ) ) {
			$where .= $this->wpdb->prepare( " AND wpml_translations.language_code='%s'", $args['to_lang'] );
			$where .= $this->wpdb->prepare( " AND wpml_translations.source_language_code='%s'", $args['from_lang'] );
		} else {
			$where .= $this->wpdb->prepare( " AND wpml_translations.language_code='%s'", $args['from_lang'] );
		}

		if ( $args['tstatus'] >= 0 ) {
			$where .= $this->build_translation_status_where( $args['tstatus'] );
		}

		return $where;
	}

	/**
	 * @param  integer $translation_status
	 * @param  string  $language
	 *
	 * @return string
	 */
	private function build_translation_status_where( $translation_status, $language = null ) {
		if ( $translation_status < 0 && ! $language ) {
			return '';
		}

		if ( $translation_status < 0 && $language ) {
			$subquery = $this->only_language_condition( $language );
		} else {
			switch ( $translation_status ) {
				case ICL_TM_NOT_TRANSLATED:
					$subquery = $this->not_translated_or_needs_update_condition( $language );
					break;
				case ICL_TM_NEEDS_UPDATE:
					$subquery = $this->needs_update_condition( $language );
					break;
				case ICL_TM_IN_PROGRESS:
					$subquery = $this->explicit_status_condition(
						wpml_prepare_in( [ ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ], '%d' ),
						$language
					);
					break;
				case ICL_TM_COMPLETE:
					$subquery = $this->explicit_status_condition(
						wpml_prepare_in( [ ICL_TM_COMPLETE, ICL_TM_DUPLICATE ], '%d' ),
						$language
					);
					break;
				default:
					$subquery = '';
			}
		}

		if ( $subquery ) {
			return " AND wpml_translations.trid IN ({$subquery})";
		}

		return '';
	}

	private function only_language_condition( $language ) {
		$query = "
			SELECT translations.trid
			FROM {$this->wpdb->prefix}icl_translations translations
			WHERE translations.language_code = %s
		";

		return $this->wpdb->prepare( $query, $language );
	}

	private function explicit_status_condition( $status, $language = null ) {
		$prefix = $this->wpdb->prefix;

		$query = "
			SELECT trid
			FROM {$prefix}icl_translations translations
			INNER JOIN {$prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			WHERE (translation_status.status IN ({$status}) AND translation_status.needs_update = 0)
		";

		if ( $language ) {
			$query .= $this->language_where( $language );
		}

		return $query;
	}

	private function needs_update_condition( $language = null ) {
		$prefix = $this->wpdb->prefix;

		$query = "
			SELECT trid
			FROM {$prefix}icl_translations translations
			INNER JOIN {$prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			WHERE translation_status.needs_update = 1
		";

		if ( $language ) {
			$query .= $this->language_where( $language );
		}

		return $query;
	}

	private function not_translated_or_needs_update_condition( $language = null ) {
		$prefix = $this->wpdb->prefix;

		$query = "
			SELECT trid
			FROM {$prefix}icl_translations translations
			INNER JOIN {$prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			WHERE ( translation_status.needs_update = 1 OR translation_status.status = 0 ) 
		";
		if ( $language ) {
			$query .= $this->language_where( $language );
		}

		$query .= ' UNION ';

		if ( $language ) {
			$query .= "
				SELECT trid
				FROM {$prefix}icl_translations translations
				WHERE NOT EXISTS (
			           SELECT inner_translations.trid
			           FROM {$prefix}icl_translations inner_translations
			           WHERE inner_translations.trid = translations.trid AND inner_translations.language_code = %s
			        )
			";
			$query  = $this->wpdb->prepare( $query, $language );
		} else {
			$query .= "
				SELECT trid
				FROM {$prefix}icl_translations translations
				WHERE (
			           SELECT COUNT(inner_translations.trid)
			           FROM {$prefix}icl_translations inner_translations
			           WHERE inner_translations.trid = translations.trid
			        ) < %d
			";
			$query  = $this->wpdb->prepare( $query, count( $this->sitepress->get_active_languages() ) );
		}

		return $query;
	}

	private function language_where( $language ) {
		return $this->wpdb->prepare( ' AND translations.language_code = %s', $language );
	}

	/**
	 * @param array  $args
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function is_cpt_type( $args = array(), $post_type = '' ) {
		$is_cpt_type = false;
		if ( ! empty( $args ) && '' === $post_type && array_key_exists( 'type', $args ) && ! empty( $args['type'] ) ) {
			$post_type = $args['type'];
		}

		if ( in_array( $post_type, $this->get_translatable_post_types() ) ) {
			$is_cpt_type = true;
		}

		return $is_cpt_type;
	}

	/**
	 * @return array
	 */
	private function get_translatable_post_types() {
		if ( null === $this->translatable_post_types ) {
			$translatable_post_types       = $this->sitepress->get_translatable_documents();
			$this->translatable_post_types = array_keys( apply_filters( 'wpml_tm_dashboard_translatable_types', $translatable_post_types ) );
		}

		return $this->translatable_post_types;
	}
}
