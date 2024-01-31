<?php

class WPML_TM_Element_Translations extends WPML_TM_Record_User {

	/** @var  int[] $trid_cache */
	private $trid_cache;
	/** @var  int[] $job_id_cache */
	private $job_id_cache;
	/** @var  int[] $job_id_cache */
	private $translation_status_cache;
	/** @var array $translation_review_status_cache */
	private $translation_review_status_cache;
	/** @var  bool[] $update_status_cache */
	private $update_status_cache;
	/** @var  string[] $element_type_prefix_cache */
	private $element_type_prefix_cache = array();

	public function init_hooks() {
		add_action( 'wpml_cache_clear', array( $this, 'reload' ) );
		add_filter(
			'wpml_tm_translation_status',
			array(
				$this,
				'get_translation_status_filter',
			),
			10,
			2
		);
	}

	public function reload() {
		$this->trid_cache                      = array();
		$this->job_id_cache                    = array();
		$this->translation_status_cache        = array();
		$this->translation_review_status_cache = array();
		$this->update_status_cache             = array();
		$this->element_type_prefix_cache       = array();
	}

	public function is_update_needed( $trid, $language_code ) {
		if ( isset( $this->update_status_cache[ $trid ][ $language_code ] ) ) {
			$needs_update = $this->update_status_cache[ $trid ][ $language_code ];
		} else {
			$this->init_job_id( $trid, $language_code );
			$needs_update = isset( $this->update_status_cache[ $trid ][ $language_code ] )
				? $this->update_status_cache[ $trid ][ $language_code ] : 0;
		}

		return (bool) $needs_update;
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return string
	 */
	public function get_element_type_prefix( $trid, $language_code ) {
		if ( $trid && $language_code && ! isset( $this->element_type_prefix_cache[ $trid ] ) ) {
			$this->init_job_id( $trid, $language_code );
		}

		return $trid && array_key_exists( $trid, $this->element_type_prefix_cache ) ? $this->element_type_prefix_cache[ $trid ] : '';
	}

	public function get_translation_status_filter( $empty, $args ) {
		$trid          = $args['trid'];
		$language_code = $args['language_code'];

		return $this->get_translation_status( $trid, $language_code );
	}
	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return int
	 */
	public function get_translation_status( $trid, $language_code ) {
		if ( isset( $this->translation_status_cache[ $trid ][ $language_code ] ) ) {
			$status = $this->translation_status_cache[ $trid ][ $language_code ];
		} else {
			$this->init_job_id( $trid, $language_code );
			$status = isset( $this->translation_status_cache[ $trid ][ $language_code ] )
				? $this->translation_status_cache[ $trid ][ $language_code ] : 0;
		}

		return (int) $status;
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 *
	 * @return string|null
	 */
	public function get_translation_review_status( $trid, $language_code ) {
		if ( ! isset( $this->translation_review_status_cache[ $trid ][ $language_code ] ) ) {
			$this->init_job_id( $trid, $language_code );
		}

		return isset( $this->translation_review_status_cache[ $trid ][ $language_code ] )
			? $this->translation_review_status_cache[ $trid ][ $language_code ] : null;
	}

	public function init_job_id( $trid, $target_lang_code ) {
		global $wpdb, $wpml_language_resolution;

		if ( ! isset( $this->job_id_cache[ $trid ][ $target_lang_code ] ) ) {
			$jobs         = $wpdb->get_results(
				$wpdb->prepare(
					$this->sql_select_for_statuses( 't.trid = %d' ),
					$trid
				)
			);
			$active_langs = $wpml_language_resolution->get_active_language_codes();

			foreach ( $active_langs as $lang_code ) {
				$this->cache_job_in_lang( $jobs, $lang_code, $trid );
			}
		}
	}

	public function init_jobs( $trids ) {
		if ( ! is_array( $trids ) || empty( $trids ) ) {
			return;
		}

		global $wpdb, $wpml_language_resolution;

		$trids         = array_map( 'absint', $trids );
		$trids         = array_unique( $trids );
		$list_of_trids = implode( ',', $trids );

		$jobs = $wpdb->get_results(
			$this->sql_select_for_statuses( "t.trid IN ($list_of_trids)" )
		);
		$active_langs = $wpml_language_resolution->get_active_language_codes();

		$jobs_per_trid = [];
		foreach( $jobs as $job ) {
			if ( ! array_key_exists( $job->trid, $jobs_per_trid ) ) {
				$jobs_per_trid[ $job->trid ] = [];
			}
			$jobs_per_trid[ $job->trid ][] = $job;
		}

		foreach ( $jobs_per_trid as $trid => $trid_jobs ) {
			foreach ( $active_langs as $lang_code ) {
				$this->cache_job_in_lang( $trid_jobs, $lang_code, $trid );
			}
		}
	}

	/**
	 * @param string $where Required: "SELECT...WHERE $where".
	 *
	 * @return string
	 */
	private function sql_select_for_statuses( $where ) {
		global $wpdb;
		return "SELECT
				t.trid,
				tj.job_id,
				ts.status,
				ts.review_status,
				ts.needs_update,
				t.language_code,
				SUBSTRING_INDEX(t.element_type, '_', 1)
				AS element_type_prefix
			FROM {$wpdb->prefix}icl_translate_job tj
			JOIN {$wpdb->prefix}icl_translation_status ts
			ON tj.rid = ts.rid
			JOIN {$wpdb->prefix}icl_translations t
			ON ts.translation_id = t.translation_id
			WHERE $where";
	}

	/**
	 * @param object[]   $jobs
	 * @param string     $lang
	 * @param int|string $trid
	 *
	 * @return false|object
	 */
	private function cache_job_in_lang( $jobs, $lang, $trid ) {
		$res = false;
		foreach ( $jobs as $job ) {
			if ( $job->language_code === $lang ) {
				$res = $job;
				break;
			}
		}

		if ( (bool) $res === true ) {
			$job_id              = $res->job_id;
			$status              = $res->status;
			$review_status       = $res->review_status;
			$needs_update        = (bool) $res->needs_update;
			$element_type_prefix = $res->element_type_prefix;
		} else {
			$job_id              = - 1;
			$status              = 0;
			$review_status       = null;
			$needs_update        = false;
			$element_type_prefix = $this->fallback_type_prefix( $trid );
		}

		$this->cache_job( (int) $trid, $lang, $job_id, $status, $review_status, $needs_update, $element_type_prefix );

		return $res;
	}

	private function fallback_type_prefix( $trid ) {
		global $wpdb;

		if ( isset( $this->element_type_prefix_cache[ $trid ] ) && (bool) $this->element_type_prefix_cache[ $trid ] === true ) {
			$prefix = $this->element_type_prefix_cache[ $trid ];
		} elseif ( (bool) $this->tm_records->get_post_translations()->get_element_translations( null, $trid ) ) {
			$prefix = 'post';
		} else {
			$prefix = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUBSTRING_INDEX(element_type, '_', 1)
					FROM {$wpdb->prefix}icl_translations
					WHERE trid = %d
					LIMIT 1",
					$trid
				)
			);
		}

		return $prefix;
	}

	/**
	 * @param int     $trid
	 * @param string  $language_code
	 * @param int     $job_id
	 * @param int     $status
	 * @param ?string $review_status
	 * @param bool    $needs_update
	 * @param string  $element_type_prefix
	 */
	private function cache_job( $trid, $language_code, $job_id, $status, $review_status, $needs_update, $element_type_prefix ) {
		if ( (bool) $job_id === true && (bool) $trid === true && (bool) $language_code === true ) {
			$this->maybe_init_trid_cache( $trid );
			$this->job_id_cache[ $trid ][ $language_code ]                    = $job_id;
			$this->translation_status_cache[ $trid ][ $language_code ]        = $status;
			$this->translation_review_status_cache[ $trid ][ $language_code ] = $review_status;
			$this->update_status_cache[ $trid ][ $language_code ]             = $needs_update;
			$this->element_type_prefix_cache[ $trid ]                         = isset( $this->element_type_prefix_cache[ $trid ] )
																					&& (bool) $this->element_type_prefix_cache[ $trid ] === true
				? $this->element_type_prefix_cache[ $trid ] : $element_type_prefix;
		}
	}

	private function maybe_init_trid_cache( $trid ) {
		foreach (
			array(
				&$this->job_id_cache,
				&$this->trid_cache,
				&$this->translation_status_cache,
				&$this->translation_review_status_cache,
				&$this->update_status_cache,
			) as $cache
		) {
			$cache[ $trid ] = isset( $cache[ $trid ] ) ? $cache[ $trid ] : array();
		}
	}
}
