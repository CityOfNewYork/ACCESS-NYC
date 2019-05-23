<?php

/**
 * Represents a helper class for building the SQL statement which retrieves the job,
 * as well as for converting this collection to specific implementations of \WPML_Element_Translation_Job.
 *
 * @package WPML\TM
 */
class WPML_Abstract_Job_Collection {
	/**
	 * Instance of \wpdb.
	 *
	 * @var \wpdb $wpdb
	 */
	public $wpdb;

	/**
	 * Instance of \SitePress.
	 *
	 * @var \SitePress
	 */
	private $sitepress;

	/**
	 * WPML_Abstract_Job_Collection constructor.
	 *
	 * @param WPDB $wpdb An instance of \wpdb.
	 */
	public function __construct( WPDB $wpdb ) {
		$this->wpdb = $wpdb;

		global $sitepress;
		$this->sitepress = $sitepress;
	}

	/**
	 * It gets the (INNER) JOIN clause of the query.
	 *
	 * @param bool   $single                            It should only return the last job revision.
	 * @param string $icl_translate_alias               The alias for `{$this->wpdb->prefix}icl_translate_job`.
	 * @param string $icl_translations_translated_alias The alias for translated documents in `{$this->wpdb->prefix}icl_translations`.
	 * @param string $icl_translations_original_alias   The alias for original documents in `{$this->wpdb->prefix}icl_translations`.
	 * @param string $icl_translation_status_alias      The alias for `{$this->wpdb->prefix}icl_translation_status`.
	 * @param string $icl_translate_job_alias           The alias for `{$this->wpdb->prefix}icl_translate_job`.
	 *
	 * @return string
	 */
	protected function get_table_join(
		$single = false,
		$icl_translate_alias = 'iclt',
		$icl_translations_translated_alias = 't',
		$icl_translations_original_alias = 'ito',
		$icl_translation_status_alias = 's',
		$icl_translate_job_alias = 'j'
	) {
		$wpdb = &$this->wpdb;

		$max_rev_snippet = '';
		if ( true !== $single ) {
			$max_rev_snippet = "JOIN (SELECT rid, MAX(job_id) job_id FROM {$wpdb->prefix}icl_translate_job GROUP BY rid ) jobmax
					ON ( {$icl_translate_job_alias}.revision IS NULL
	                    AND {$icl_translate_job_alias}.rid = jobmax.rid)
                        OR ( {$icl_translate_job_alias}.job_id = jobmax.job_id
                        AND {$icl_translate_job_alias}.translated = 1)";
		}

		return "{$wpdb->prefix}icl_translate_job {$icl_translate_job_alias}
                JOIN {$wpdb->prefix}icl_translation_status {$icl_translation_status_alias}
                  ON {$icl_translate_job_alias}.rid = {$icl_translation_status_alias}.rid
                JOIN {$wpdb->prefix}icl_translations {$icl_translations_translated_alias}
                  ON {$icl_translation_status_alias}.translation_id = {$icl_translations_translated_alias}.translation_id
                JOIN {$wpdb->prefix}icl_translate {$icl_translate_alias}
                  ON {$icl_translate_alias}.job_id = {$icl_translate_job_alias}.job_id
                JOIN {$wpdb->prefix}icl_translations {$icl_translations_original_alias}
                  ON {$icl_translations_original_alias}.element_id = {$icl_translate_alias}.field_data
                    AND {$icl_translations_original_alias}.trid = {$icl_translations_translated_alias}.trid
                {$max_rev_snippet}";
	}

	/**
	 * It gets the LEFT JOIN clause of the query.
	 *
	 * @param string $icl_translations_original_alias The alias for original documents in `{$this->wpdb->prefix}icl_translations`.
	 * @param string $posts_alias                     The alias for `{$this->wpdb->prefix}posts`.
	 *
	 * @return array
	 */
	protected function left_join_post( $icl_translations_original_alias = 'ito', $posts_alias = 'p' ) {

		$join   = "LEFT JOIN {$this->wpdb->prefix}posts {$posts_alias}
                  ON {$icl_translations_original_alias}.element_id = {$posts_alias}.ID
                     AND {$icl_translations_original_alias}.element_type = CONCAT('post_', {$posts_alias}.post_type)";
		$select = "IF({$posts_alias}.post_type IS NOT NULL, 'post', 'package') as element_type_prefix";

		return array( $select, $join );
	}

	/**
	 * It converts an array of \stdClass jobs into an array of \WPML_Element_Translation_Job instances.
	 *
	 * @param array $jobs The array of \stdClass jobs.
	 *
	 * @return \WPML_Element_Translation_Job[]|\WPML_Post_Translation_Job[]|\WPML_String_Translation_Job[]|\WPML_External_Translation_Job[]
	 */
	protected function plain_objects_to_job_instances( $jobs ) {
		foreach ( $jobs as $key => $job ) {
			if ( ! is_object( $job ) || ! isset( $job->element_type_prefix ) || ! isset( $job->job_id ) ) {
				unset( $jobs[ $key ] );
				continue;
			}

			if ( 'post' === $job->element_type_prefix ) {
				$post_translation_job = new WPML_Post_Translation_Job( $job->job_id, $job->batch_id );
				if ( $post_translation_job->is_translatable_post_type() ) {
					$jobs[ $key ] = $post_translation_job;
				} else {
					unset( $jobs[ $key ] );
				}
			} elseif ( 'string' === $job->element_type_prefix ) {
				$jobs[ $key ] = new WPML_String_Translation_Job( $job->job_id );
			} else {
				$jobs[ $key ] = new WPML_External_Translation_Job( $job->job_id, $job->batch_id );
			}
		}

		return $jobs;
	}

	/**
	 * Optional arguments to filter the results.
	 *
	 * @param array $args {
	 *                    Optional. An array of arguments.
	 *
	 * @type int    translator_id
	 * @type int    status
	 * @type int    status__not
	 * @type bool   include_unassigned
	 * @type int    limit_no
	 * @type array  language_pairs
	 * @type string service
	 * @type string from
	 * @type string to
	 * @type string type
	 * @type bool   overdue
	 * @type string   title
	 * }
	 *
	 * @return string
	 */
	protected function build_where_clause( array $args ) {
		$defaults_args = array(
			'translator_id'      => 0,
			'status'             => false,
			'status__not'        => false,
			'include_unassigned' => false,
			'language_pairs'     => array(),
			'service'            => 0,
			'from'               => null,
			'to'                 => null,
			'type'               => null,
			'overdue'            => false,
			'title'              => null,
		);

		$args = array_merge( $defaults_args, $args );

		$translator_id      = $args['translator_id'];
		$status             = $args['status'];
		$status__not        = $args['status__not'];
		$include_unassigned = $args['include_unassigned'];
		$language_pairs     = $args['language_pairs'];
		$service            = $args['service'];
		$from               = $args['from'];
		$to                 = $args['to'];
		$type               = $args['type'];
		$overdue            = $args['overdue'];
		$title              = $args['title'];

		$where = ' s.status > ' . ICL_TM_NOT_TRANSLATED;

		if ( $status ) {
			$where .= $this->wpdb->prepare( ' AND s.status = %d', (int) $status );
		}
		if ( ICL_TM_DUPLICATE !== $status ) {
			$where .= $this->wpdb->prepare( ' AND s.status <> %d ', ICL_TM_DUPLICATE );
		}
		if ( false !== $status__not ) {
			$where .= $this->wpdb->prepare( ' AND s.status <> %d ', $status__not );
		}
		if ( $from ) {
			$where .= $this->wpdb->prepare( ' AND t.source_language_code = %s ', $from );
		}
		if ( $to ) {
			$where .= $this->wpdb->prepare( ' AND t.language_code = %s ', $to );
		}
		if ( $title ) {
			$where .= $this->wpdb->prepare( ' AND p.post_title LIKE %s ', '%' . $title . '%' );
		}

		if ( '' !== $translator_id ) {
			if ( ! is_numeric( $translator_id ) ) {
				$_exp          = explode( '-', $translator_id );
				$service       = isset( $_exp[1] ) ? implode( '-', array_slice( $_exp, 1 ) ) : 'local';
				$translator_id = isset( $_exp[2] ) ? $_exp[2] : false;
			} elseif ( ! $service && ( ! isset( $args['any_translation_service'] ) || ! $args['any_translation_service'] ) ) {
				$service = 'local';
			}

			$language_pairs = empty( $to ) || empty( $from ) ?
				get_user_meta( $translator_id, $this->wpdb->prefix . 'language_pairs', true )
				: $language_pairs;

			$translator_id_query_parts = array();
			if ( 0 !== (int) $translator_id ) {
				$translator_id_query_parts[] = $this->wpdb->prepare( 'j.translator_id = %d', $translator_id );
				if ( $include_unassigned ) {
					$translator_id_query_parts[] = ' j.translator_id = 0 OR j.translator_id IS NULL ';
				}
				if ( true === (bool) $translator_id_query_parts ) {
					$where .= ' AND (' . join( ' OR ', $translator_id_query_parts ) . ') ';
				}
			}
		}

		$where .= ! empty( $service ) ? $this->wpdb->prepare( ' AND s.translation_service=%s ', $service ) : '';

		if ( $this->sitepress ) {
			$post_types = array_keys( $this->sitepress->get_translatable_documents() );
			if ( $post_types ) {
				$where .= ' AND (p.post_type IS NULL || p.post_type IN (' . wpml_prepare_in( $post_types, '%s' ) . ' )) ';
			}
		}

		if ( empty( $from ) && false !== (bool) $language_pairs && is_array( $language_pairs ) && $translator_id ) {
			/**
			 * Only if we filter by translator, make sure to use just the 'from' languages that apply
			 * in no translator_id, omit condition and all will be pulled.
			 */
			if ( ! empty( $to ) ) {
				/**
				 * Get 'from' languages corresponding to $to (to $translator_id).
				 */
				$from_languages = array();
				foreach ( $language_pairs as $fl => $tls ) {
					if ( isset( $tls[ $to ] ) ) {
						$from_languages[] = $fl;
					}
				}
				if ( $from_languages ) {
					$where .= ' AND t.source_language_code IN (' . wpml_prepare_in( $from_languages ) . ') ';
				}
			} else {
				/**
				 * All to all case.
				 * Get all possible combinations for $translator_id.
				 */
				$from_languages   = array_keys( $language_pairs );
				$where_conditions = array();
				foreach ( $from_languages as $fl ) {
					$prepared_in_values = wpml_prepare_in( array_keys( $language_pairs[ $fl ] ) );
					$where_conditions[] = ' (' . $this->wpdb->prepare( 't.source_language_code = %s', $fl ) . ' AND t.language_code IN (' . $prepared_in_values . ')) ';
				}

				if ( ! empty( $where_conditions ) ) {
					$where .= ' AND ( ' . join( ' OR ', $where_conditions ) . ') ';
				}
			}
		}

		if ( empty( $to ) && $translator_id && ! empty( $from ) && isset( $language_pairs[ $from ] ) && false !== (bool) $language_pairs[ $from ] ) {
			/**
			 * Only if we filter by translator, make sure to use just the 'from' languages that apply
			 * in no translator_id, omit condition and all will be pulled.
			 * Get languages the user can translate into from $from.
			 */
			$where .= ' AND t.language_code IN(' . wpml_prepare_in( array_keys( $language_pairs[ $from ] ) ) . ')';
		}

		$where .= ! empty( $type ) ? $this->wpdb->prepare( ' AND ito.element_type=%s ', $type ) : '';

		if ( $overdue ) {
			$today_date = date( 'Y-m-d' );

			$where .= $this->wpdb->prepare( " AND j.deadline_date IS NOT NULL AND j.completed_date IS NULL AND j.deadline_date < %s AND j.deadline_date <> '0000-00-00 00:00:00'", $today_date );
		}

		return $where;
	}
}
