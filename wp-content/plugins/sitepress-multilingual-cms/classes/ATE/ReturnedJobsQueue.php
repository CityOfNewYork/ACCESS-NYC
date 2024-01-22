<?php

namespace WPML\TM\ATE;

use WPML\FP\Obj;
use function WPML\Container\make;

/**
 * Class ReturnedJobsQueue
 *
 * @package WPML\TM\ATE
 *
 * IMPORTANT!
 * In this class `wpmlJobId` represents job_id column in icl_translate_job
 */
class ReturnedJobsQueue {

	const OPTION_NAME      = 'ATE_RETURNED_JOBS_QUEUE';
	const STATUS_COMPLETED = 'complete';
	const STATUS_BACK      = 'back';

	/**
	 * @param  int      $ateJobId
	 * @param  string   $status
	 * @param  callable $ateIdToWpmlId @see comment in the class description
	 */
	public static function add( $ateJobId, $status, callable $ateIdToWpmlId ) {
		$wpmlId = $ateIdToWpmlId( $ateJobId );

		if ( in_array( $status, [ self::STATUS_BACK, self::STATUS_COMPLETED ] ) && $wpmlId ) {
			$options            = get_option( self::OPTION_NAME, [] );
			$options[ $wpmlId ] = $status;
			update_option( self::OPTION_NAME, $options );
		}
	}

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

	/**
	 * @param  int $wpmlJobId @see comment in the class description
	 *
	 * @return string|null
	 */
	public static function getStatus( $wpmlJobId ) {
		return Obj::prop( $wpmlJobId, get_option( self::OPTION_NAME, [] ) );
	}

	/**
	 * @param $wpmlJobId @see comment in the class description
	 */
	public static function remove( $wpmlJobId ) {
		$options = get_option( self::OPTION_NAME, [] );
		if ( isset( $options[ $wpmlJobId ] ) ) {
			unset( $options[ $wpmlJobId ] );
			update_option( self::OPTION_NAME, $options );
		}
	}
}
