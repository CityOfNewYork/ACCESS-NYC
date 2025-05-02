<?php

namespace WPML\TM\ATE;


use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\ATE\Review\ReviewStatus;

class Jobs {
	const LONGSTANDING_AT_ATE_SYNC_COUNT = 100;

	/**
	 * Each string inside string batch is counted separately.
	 * Therefore, if we have two string batches and the first one has 3 strings inside and another 2,
	 * we will count it as 5=3+2 instead of 2.
	 *
	 * @param bool $includeLongstanding A long-standing job is an automatic ATE job which we already tried to sync LONGSTANDING_AT_ATE_SYNC_COUNT or more times.
	 * @return int
	 */
	public function getCountOfAutomaticInProgress( $includeLongstanding = true ) {
		global $wpdb;

		/**
		 * Notice that we have the LEFT JOIN on `icl_string_batches` table.
		 * This is relevant only for string jobs. In case of the posts, it will do nothing.
		 * We need that join to count individual strings inside a string batch.
		 */
		$sql = "
				SELECT COUNT(jobs.job_id)
				FROM {$wpdb->prefix}icl_translate_job jobs
				INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
				INNER JOIN {$wpdb->prefix}icl_translations translations ON translations.translation_id = translation_status.translation_id
				";

		if ( wpml_is_st_loaded() ) {
			$sql .= "
				LEFT JOIN {$wpdb->prefix}icl_translations original_translations ON
				    original_translations.trid = translations.trid AND original_translations.source_language_code IS NULL
				LEFT JOIN {$wpdb->prefix}icl_string_batches string_batches ON 
					string_batches.batch_id = original_translations.element_id AND translations.element_type = 'st-batch_strings'
        ";
		}
		$sql .= "
				WHERE jobs.job_id IN (
					SELECT MAX(jobs.job_id) FROM {$wpdb->prefix}icl_translate_job jobs			
					GROUP BY jobs.rid
				) 
				AND jobs.automatic = 1  
				AND jobs.editor = %s
				AND translation_status.status = %d				
				AND translations.source_language_code = %s
		";

		if ( ! $includeLongstanding ) {
			$sql .= " AND jobs.ate_sync_count < %d";

			return (int) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS, Languages::getDefaultCode(), self::LONGSTANDING_AT_ATE_SYNC_COUNT ) );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS, Languages::getDefaultCode() ) );
	}

	/**
	 * @return int
	 */
	public function getCountOfInProgress() {
		global $wpdb;

		$sql = "
				SELECT COUNT(jobs.job_id)
				FROM {$wpdb->prefix}icl_translate_job jobs
				INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
				WHERE jobs.job_id IN (
					SELECT MAX(jobs.job_id) FROM {$wpdb->prefix}icl_translate_job jobs			
					GROUP BY jobs.rid
				) 
				AND jobs.editor = %s
				AND translation_status.status = %d				
		";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS ) );
	}

	/**
	 * @return int
	 */
	public function getCountOfNeedsReview() {
		global $wpdb;

		$sql = "
			SELECT COUNT(translation_status.translation_id) 
			FROM {$wpdb->prefix}icl_translation_status translation_status
			INNER JOIN {$wpdb->prefix}icl_translations translations ON 
			    translations.translation_id = translation_status.translation_id AND translations.element_id IS NOT NULL			
			WHERE ( translation_status.review_status = %s AND translation_status.status = %d ) OR 
			      ( translation_status.review_status = %s AND translation_status.status = %d )
		";

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$sql,
				ReviewStatus::NEEDS_REVIEW,
				ICL_TM_COMPLETE,
				ReviewStatus::EDITING,
				ICL_TM_IN_PROGRESS
			)
		);
	}


	/**
	 * It checks whether we have ANY jobs in the DB. It doesn't matter what kind of jobs they are. It can be a job from ATE, CTE or even the Translation Proxy.
	 *
	 * @return bool
	 * @todo This method should not be here as the current class relates solely to ATE jobs, while this method asks for ANY jobs.
	 */
	public function hasAny() {
		global $wpdb;

		$noOfRowsToFetch = 1;

		$sql = $wpdb->prepare( "SELECT EXISTS(SELECT %d FROM {$wpdb->prefix}icl_translate_job)", $noOfRowsToFetch );

		return boolval( $wpdb->get_var( $sql ) );
	}

	/**
	 * @return bool True if there is at least one job to sync.
	 */
	public function hasAnyToSync() {
		global $wpdb;

		$sql = "
				SELECT jobs.job_id
				FROM {$wpdb->prefix}icl_translate_job jobs
				INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
				WHERE jobs.job_id IN (
					SELECT MAX(jobs.job_id) FROM {$wpdb->prefix}icl_translate_job jobs			
					GROUP BY jobs.rid
				) 
				AND jobs.editor = %s
				AND translation_status.status = %d
				LIMIT 1
		";

		return (bool) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS ) );
	}

	/**
	 * This is optimized query for getting the ate job ids to sync.
	 *
	 * @param bool $includeManualAndLongstandingJobs
	 * @return int[]
	 */
	public function getATEJobIdsToSync( $includeManualAndLongstandingJobs = true ) {
		global $wpdb;

		$sql = "
				SELECT jobs.editor_job_id
				FROM {$wpdb->prefix}icl_translate_job jobs
			    INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
				WHERE jobs.job_id IN (
	                SELECT MAX(jobs.job_id) FROM {$wpdb->prefix}icl_translate_job jobs			
					GROUP BY jobs.rid
				) 
	            AND jobs.editor = %s
				AND ( translation_status.status = %d OR translation_status.status = %d )
		";

		if ( ! $includeManualAndLongstandingJobs ) {
			$sql .= " AND jobs.ate_sync_count < %d AND jobs.automatic = 1";

			return $wpdb->get_col( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR, self::LONGSTANDING_AT_ATE_SYNC_COUNT ) );
		}

		return $wpdb->get_col( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ) );
	}
}
