<?php

namespace WPML\TM\ATE;

use WPML\FP\Obj;

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
