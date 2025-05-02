<?php

/**
 * Class WPML_TM_Batch_Report
 */
class WPML_TM_Batch_Report {

	const BATCH_REPORT_OPTION = '_wpml_batch_report';

	/**
	 * @var WPML_TM_Blog_Translators
	 */
	private $blog_translators;

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/** @var array */
	private $delayed;

	/**
	 * WPML_TM_Batch_Report constructor.
	 *
	 * @param WPML_TM_Blog_Translators $blog_translators
	 * @param \wpdb                    $wpdb
	 */
	public function __construct( WPML_TM_Blog_Translators $blog_translators, \wpdb $wpdb ) {
		$this->blog_translators = $blog_translators;
		$this->wpdb             = $wpdb;
		$this->delayed          = [];
	}

	/**
	 * @param WPML_Translation_Job $job
	 */
	public function set_job( WPML_Translation_Job $job ) {
		$job_fields = $job->get_basic_data();
		if ( ! WPML_User_Jobs_Notification_Settings::is_new_job_notification_enabled( $job_fields->translator_id ) ) {
			return;
		}

		$batch_jobs = $batch_jobs_raw = $this->get_jobs();
		$batch_jobs = $this->add_job_to_batch( $batch_jobs, $job, $job_fields );

		if ( $batch_jobs_raw !== $batch_jobs ) {
			update_option( self::BATCH_REPORT_OPTION, $batch_jobs, 'no' );
		}
	}

	/**
	 * @param WPML_Translation_Job $job
	 */
	public function set_job_with_delay( WPML_Translation_Job $job ) {
		$job_fields = $job->get_basic_data();
		if ( ! WPML_User_Jobs_Notification_Settings::is_new_job_notification_enabled( $job_fields->translator_id ) ) {
			return;
		}

		$delayed_batch = $this->delayed;
		$this->delayed = $this->add_job_to_batch( $delayed_batch, $job, $job_fields );
	}

	/**
	 * @param array                $batch
	 * @param WPML_Translation_Job $job
	 * @param object               $job_fields
	 *
	 * @return array
	 */
	private function add_job_to_batch( $batch, $job, $job_fields ) {
		$lang_pair  = $job_fields->source_language_code . '|' . $job_fields->language_code;

		$batch[ (int) $job_fields->translator_id ][ $lang_pair ][] = array(
			'element_id' => isset( $job_fields->original_doc_id ) ? $job_fields->original_doc_id : null,
			'type'       => strtolower( $job->get_type() ),
			'job_id'     => $job->get_id(),
		);

		return $batch;
	}

	/**
	 * @return array
	 */
	public function get_unassigned_jobs() {
		$batch_jobs = $this->get_jobs();
		$unassigned_jobs = array();

		if( array_key_exists( 0, $batch_jobs ) ) {
			$unassigned_jobs = $batch_jobs[0];
		}

		return $unassigned_jobs;
	}

	/**
	 * @return array
	 */
	public function get_unassigned_translators() {
		$assigned_translators = array_keys( $this->get_jobs() );
		$blog_translators = wp_list_pluck( $this->blog_translators->get_blog_translators() , 'ID');

		return array_diff( $blog_translators, $assigned_translators );
	}

	/**
	 * Cleans batch jobs by validating language pairs and ensuring each job ID exists in the database.
	 *
	 * @return void
	 */
	public function clean_batch_jobs()
	{
		global $sitepress;

		$batch_jobs = $this->get_batch_jobs();
		if ( empty( $batch_jobs ) ) {
			return;
		}

		$valid_language_codes = array_fill_keys( array_keys( $sitepress->get_active_languages() ), true );

		// Filter jobs by active languages and collect job IDs.
		list( $filtered_jobs, $job_ids ) = $this->filter_jobs_by_active_languages_and_collect_job_ids( $batch_jobs, $valid_language_codes );

		// If no job IDs were collected, update the option and exit.
		if ( empty( $job_ids ) ) {
			$this->update_batch_jobs_option( $filtered_jobs );
			return;
		}

		// Validate the collected job IDs against the database.
		$valid_job_ids_set = $this->get_valid_job_ids_set( $job_ids );

		// Remove job items that are not in the valid job IDs set.
		$cleaned_jobs = $this->remove_invalid_jobs( $filtered_jobs, $valid_job_ids_set );

		$this->update_batch_jobs_option( $cleaned_jobs );
	}

	/**
	 * Retrieves the batch jobs option.
	 *
	 * @return array Batch jobs array, or an empty array if not valid.
	 */
	private function get_batch_jobs()
	{
		$batch_jobs = get_option( self::BATCH_REPORT_OPTION );
		return ( empty( $batch_jobs ) || !is_array( $batch_jobs ) ) ? array() : $batch_jobs;
	}

	/**
	 * Updates the batch jobs option with the provided jobs array.
	 *
	 * @param array $jobs Batch jobs array.
	 * @return void
	 */
	private function update_batch_jobs_option( array $jobs )
	{
		update_option( self::BATCH_REPORT_OPTION, $jobs, 'no' );
	}

	/**
	 * Filters batch jobs by checking active language pairs and collects job IDs.
	 *
	 * @param array $batch_jobs Batch jobs.
	 * @param array $valid_language_codes Array of valid language codes.
	 * @return array An array with two elements: the filtered jobs and the collected job IDs.
	 */
	private function filter_jobs_by_active_languages_and_collect_job_ids( array $batch_jobs, array $valid_language_codes )
	{
		$filtered_jobs = array();
		$job_ids       = array();

		foreach ( $batch_jobs as $translator_id => $language_pairs ) {
			foreach ( $language_pairs as $language_pair_name => $language_pair_items ) {
				$languages = explode( '|', $language_pair_name );
				// Ensure the language pair format is valid.
				if ( count( $languages ) !== 2 ) {
					continue;
				}

				list( $source_lang, $target_lang ) = $languages;

				if ( !isset( $valid_language_codes[ $source_lang ] ) || !isset( $valid_language_codes[ $target_lang ] ) ) {
					continue;
				}

				$filtered_jobs[ $translator_id ][ $language_pair_name ] = $language_pair_items;
				foreach ( $language_pair_items as $item ) {
					if ( !empty( $item[ 'job_id' ] ) ) {
						$job_ids[] = (int)$item[ 'job_id' ];
					}
				}
			}
		}

		return array( $filtered_jobs, $job_ids );
	}

	/**
	 * Validates the collected job IDs against the database.
	 *
	 * @param array $job_ids Collected job IDs.
	 * @return array A set of valid job IDs.
	 */
	private function get_valid_job_ids_set( array $job_ids )
	{
		$job_ids           = array_unique( $job_ids );
		$valid_job_ids_set = array();
		$chunk_size        = 500;
		$chunks            = array_chunk( $job_ids, $chunk_size );

		foreach ( $chunks as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$table        = $this->wpdb->prefix . 'icl_translate_job';
			$sql          = "SELECT job_id FROM {$table} WHERE job_id IN ($placeholders)";
			$results      = $this->wpdb->get_col( $this->wpdb->prepare( $sql, $chunk ) );

			if ( !empty( $results ) ) {
				foreach ( $results as $job_id ) {
					$valid_job_ids_set[ (int)$job_id ] = true;
				}
			}
		}

		return $valid_job_ids_set;
	}

	/**
	 * Removes invalid job items from the filtered jobs based on the valid job IDs set.
	 *
	 * @param array $filtered_jobs Filtered jobs array.
	 * @param array $valid_job_ids_set Set of valid job IDs.
	 * @return array Cleaned jobs array.
	 */
	private function remove_invalid_jobs( array $filtered_jobs, array $valid_job_ids_set )
	{
		foreach ( $filtered_jobs as $translator_id => $language_pairs ) {
			foreach ( $language_pairs as $language_pair_name => $language_pair_items ) {
				foreach ( $language_pair_items as $index => $item ) {
					$job_id = isset( $item[ 'job_id' ] ) ? (int)$item[ 'job_id' ] : 0;
					// Remove the item if job_id exists but is not valid.
					if ( $job_id && !isset( $valid_job_ids_set[ $job_id ] ) ) {
						unset( $filtered_jobs[ $translator_id ][ $language_pair_name ][ $index ] );
					}
				}
				if ( empty( $filtered_jobs[ $translator_id ][ $language_pair_name ] ) ) {
					unset( $filtered_jobs[ $translator_id ][ $language_pair_name ] );
				}
			}
			if ( empty( $filtered_jobs[ $translator_id ] ) ) {
				unset( $filtered_jobs[ $translator_id ] );
			}
		}

		return $filtered_jobs;
	}

	/**
	 * @return array
	 */
	public function get_jobs() {
		$jobs = get_option( self::BATCH_REPORT_OPTION ) ? get_option( self::BATCH_REPORT_OPTION ) : array();

		$jobIds = [];
		foreach ( $jobs as $translatorId => $languagePairs ) {
			if ( ! is_array( $languagePairs ) ) {
				continue;
			}

			foreach ( $languagePairs as $languagePairName => $languagePairItems ) {
				foreach ( $languagePairItems as $languagePairItem ) {
					if ( isset( $languagePairItem['job_id'] ) ) {
						$jobIds[] = $languagePairItem['job_id'];
					}
				}
			}
		}

		if ( empty( $jobIds ) ) {
			return $jobs;
		}

		$jobIdsIn        = wpml_prepare_in( $jobIds, '%d' );
		$automaticJobIds = $this->wpdb->get_col( $this->wpdb->prepare(
			"
			SELECT job_id
			FROM {$this->wpdb->prefix}icl_translate_job
			WHERE job_id IN({$jobIdsIn}) AND automatic = 1
			LIMIT %d
			",
			count( $jobIds )
		) );
		$automaticJobIds = array_map('intval', is_array( $automaticJobIds ) ? $automaticJobIds : [] );

		$filteredJobs = [];
		foreach ( $jobs as $translatorId => $languagePairs ) {
			foreach ( $languagePairs as $languagePairName => $languagePairItems ) {
				foreach ( $languagePairItems as $languagePairItem ) {
					if ( ! isset( $languagePairItem['job_id'] ) || in_array( (int) $languagePairItem['job_id'], $automaticJobIds ) ) {
						continue;
					}

					if ( ! isset( $filteredJobs[ $translatorId ] ) ) {
						$filteredJobs[ $translatorId ] = [];
					}

					if ( ! isset( $filteredJobs[ $translatorId ][ $languagePairName ] ) ) {
						$filteredJobs[ $translatorId ][ $languagePairName ] = [];
					}

					$filteredJobs[ $translatorId ][ $languagePairName ][] = $languagePairItem;
				}
			}
		}

		return $filteredJobs;
	}

	public function process_jobs_with_delay() {
		if ( empty( $this->delayed ) ) {
			return;
		}

		$batch_jobs = $this->get_jobs();

		foreach( $this->delayed as $translator_id => $languagePairs ) {
			foreach ( $languagePairs as $languagePairName => $languagePairItems ) {
				foreach ( $languagePairItems as $languagePairItem ) {
					$batch_jobs[ (int) $translator_id ][ $languagePairName ][] = $languagePairItem;
				}
			}
		}

		$this->delayed = [];

		update_option( self::BATCH_REPORT_OPTION, $batch_jobs, 'no' );
	}

	public function reset_batch_report( $translator_id ) {
		$batch_jobs = $this->get_jobs();
		if ( array_key_exists( $translator_id, $batch_jobs ) ) {
			unset( $batch_jobs[$translator_id] );
		}

		update_option( self::BATCH_REPORT_OPTION, $batch_jobs, 'no' );
	}

	/**
	 * @param int[] $translators_ids
	 */
	public function reset_batch_report_for_translators( $translators_ids ) {
		$batch_jobs      = $this->get_jobs();
		$translators_ids = array_unique( $translators_ids );

		if ( empty( $translators_ids ) ) {
			return;
		}

		foreach ( $translators_ids as $translator_id ) {
			if ( array_key_exists( $translator_id, $batch_jobs ) ) {
				unset( $batch_jobs[$translator_id] );
			}
		}

		update_option( self::BATCH_REPORT_OPTION, $batch_jobs, 'no' );
	}
}
