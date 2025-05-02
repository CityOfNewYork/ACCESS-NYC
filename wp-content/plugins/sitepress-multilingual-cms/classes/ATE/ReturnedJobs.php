<?php

namespace WPML\TM\ATE;

use WPML\FP\Obj;
use function WPML\Container\make;

/**
 * @package WPML\TM\ATE
 */
class ReturnedJobs {

	/** @var callable(int): int  It maps ate_job_id to job_id value inside wp_icl_translate_job table */
	private $ateIdToWpmlId;

	/**
	 * For jobs that are completed in ATE, but belong to a Translation that is currently marked as "Duplicate".
	 * In such cases, we want to get rid of the Duplicate status, otherwise it will not be processed during ATE sync.
	 *
	 * @see \WPML\TM\ATE\Loader::getData
	 * @see \WPML_Meta_Boxes_Post_Edit_HTML::post_edit_languages_duplicate_of How it's handled for CTE.
	 *
	 * @param int      $ateJobId
	 * @param callable $ateIdToWpmlId
	 */
	public static function removeJobTranslationDuplicateStatus( $ateJobId, callable $ateIdToWpmlId ) {
		$wpmlJobId = $ateIdToWpmlId( $ateJobId );

		if ( $wpmlJobId ) {
			/** @var \WPML_TM_Records $tm_records */
			$tm_records        = make( \WPML_TM_Records::class );
			$jobTranslation    = $tm_records->icl_translate_job_by_job_id( $wpmlJobId );
			$translationStatus = $tm_records->icl_translation_status_by_rid( $jobTranslation->rid() );
			if ( ICL_TM_DUPLICATE === $translationStatus->status() ) {
				$translationStatus->update( [ 'status' => ICL_TM_IN_PROGRESS ] );
			}
		}
	}
}
