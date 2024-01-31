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
	 * @param array $statuses
	 *
	 * @return array
	 */
	public static function getJobsWithStatus( array $statuses ) {
		if ( ! $statuses ) {
			return [];
		}

		global $wpdb;

		$needsReviewCondition = '1=0';
		if ( Lst::includes( ICL_TM_NEEDS_REVIEW, $statuses ) ) {
			$reviewStatuses             = wpml_prepare_in( [ ReviewStatus::NEEDS_REVIEW, ReviewStatus::EDITING ] );
			$needsReviewCondition = 'translation_status.review_status IN ( ' . $reviewStatuses . ' )';
		}

		$statuses  = \wpml_prepare_in( $statuses, '%d' );
		$languages = \wpml_prepare_in( Lst::pluck( 'code', Languages::getActive() ) );

		$sql = "
			SELECT jobs.rid, jobs.job_id as jobId, jobs.editor_job_id as ateJobId, jobs.automatic , translation_status.status,
				translation_status.review_status, jobs.ate_sync_count > " . static::LONGSTANDING_AT_ATE_SYNC_COUNT . " as isLongstanding
			FROM {$wpdb->prefix}icl_translate_job as jobs
			INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
			INNER JOIN {$wpdb->prefix}icl_translations translations ON translation_status.translation_id = translations.translation_id
			INNER JOIN {$wpdb->prefix}icl_translations parent_translations ON translations.trid = parent_translations.trid
			AND parent_translations.source_language_code IS NULL
			LEFT JOIN {$wpdb->prefix}posts posts ON parent_translations.element_id = posts.ID 
			WHERE 
			    jobs.job_id IN  (
			        SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job 
			        GROUP BY rid
			    )
				AND jobs.editor = %s 
				AND ( translation_status.status IN ({$statuses}) OR $needsReviewCondition )
				AND translations.language_code IN ({$languages})
				AND translations.source_language_code IS NOT NULL
				AND ( posts.post_status IS NULL OR posts.post_status <> 'trash' )
		";

		return Fns::map( Obj::evolve( [
			'rid'            => Cast::toInt(),
			'jobId'          => Cast::toInt(),
			'ateJobId'       => Cast::toInt(),
			'automatic'      => Cast::toBool(),
			'status'         => Cast::toInt(),
			'isLongstanding' => Cast::toBool(),
		] ), $wpdb->get_results( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE ) ) );
	}

	/**
	 * @return array
	 */
	public static function getJobsToSync() {
		return self::getJobsWithStatus( [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS, ICL_TM_ATE_NEEDS_RETRY ] );
	}

	/**
	 * @return int
	 */
	public static function getTotal() {
		global $wpdb;

		$sql = "
			SELECT COUNT(jobs.job_id)
			FROM {$wpdb->prefix}icl_translate_job as jobs
			WHERE jobs.editor = %s
		";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE ) );
	}

	/**
	 * @return int
	 */
	public static function getCountOfAutomaticInProgress() {
		global $wpdb;

		$sql = "
				SELECT COUNT(jobs.job_id)
				FROM {$wpdb->prefix}icl_translate_job jobs
				INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
				INNER JOIN {$wpdb->prefix}icl_translations translations ON translations.translation_id = translation_status.translation_id
				WHERE jobs.job_id IN (
					SELECT MAX(jobs.job_id) FROM {$wpdb->prefix}icl_translate_job jobs			
					GROUP BY jobs.rid
				) 
				AND jobs.automatic = 1  
				AND jobs.editor = %s
				AND translation_status.status = %d				
				AND translations.source_language_code = %s
		";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, \WPML_TM_Editors::ATE, ICL_TM_IN_PROGRESS, Languages::getDefaultCode() ) );
	}

	/**
	 * @return bool
	 */
	public static function isThereJob() {
		global $wpdb;

		$noOfRowsToFetch = 1;

		$sql = $wpdb->prepare( "SELECT EXISTS(SELECT %d FROM {$wpdb->prefix}icl_translate_job)", $noOfRowsToFetch );

		return boolval( $wpdb->get_var( $sql ) );
	}
}
