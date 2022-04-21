<?php

namespace WPML\TM\API\Job;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\FP\curryN;

/**
 * @method static callable|int fromJobId( ...$job_id )
 * @method static callable|int|null fromRid( ...$rid )
 */
class Map {
	use Macroable;

	private static $rid_to_jobId = [];

	public static function init() {
		self::macro( 'fromJobId', curryN( 1, Fns::memorize( function ( $jobId ) {
			$rid = Obj::prop( $jobId, array_flip( array_filter( self::$rid_to_jobId ) ) );
			if ( $rid ) {
				return $rid;
			}

			$rid = self::ridFromDB( $jobId );
			self::$rid_to_jobId[$rid] = $jobId;

			return $rid;
		})));

		self::macro( 'fromRid', curryN( 1, function ( $rid ) {
			$jobId = Obj::prop( $rid, self::$rid_to_jobId );
			if ( $jobId ) {
				return $jobId;
			}

			$jobId                      = self::jobIdFromDB( $rid );
			self::$rid_to_jobId[ $rid ] = $jobId;

			return $jobId;
		} ) );
	}

	public static function jobIdFromDB( $rid ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d",
				$rid
			)
		);
	}

	public static function ridFromDB( $jobId ) {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d",
				$jobId
			)
		);
	}
}

Map::init();
