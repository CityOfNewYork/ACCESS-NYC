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
			$reviewStatuses             = wpml_prepare_in( [ ReviewStatus::NEEDS_REVIEW, ReviewStatus::EDITING ], '%d' );
			$needsReviewCondition = 'translation_status.review_status IN ( ' . $reviewStatuses . ' )';
		}

		$statuses  = \wpml_prepare_in( $statuses, '%d' );
		$languages = \wpml_prepare_in( Lst::pluck( 'code', Languages::getActive() ) );

		$sql = "
			SELECT jobs.rid, MAX(jobs.job_id) as jobId, jobs.editor_job_id as ateJobId, jobs.automatic , translation_status.status,
				translation_status.review_status, jobs.ate_sync_count > " . static::LONGSTANDING_AT_ATE_SYNC_COUNT . " as isLongstanding
			FROM {$wpdb->prefix}icl_translate_job as jobs
			INNER JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = jobs.rid
			LEFT JOIN {$wpdb->prefix}icl_translations translations ON translation_status.translation_id = translations.translation_id 
			WHERE 
				jobs.editor = %s 
				AND ( translation_status.status IN ({$statuses}) OR $needsReviewCondition )
				AND translations.language_code IN ({$languages})				
			GROUP BY jobs.rid;
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
		return self::getJobsWithStatus( [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ] );
	}

	/**
	 * @return array
	 */
	public static function getJobsToRetry() {
		return self::getJobsWithStatus( [ ICL_TM_ATE_NEEDS_RETRY ] );
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
}
