<?php

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Sync\Arguments;
use WPML\TM\ATE\Sync\Process;
use WPML\TM\ATE\Sync\Result;
use WPML\TM\ATE\SyncLock;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;
use WPML_TM_ATE_Job;
use function WPML\Container\make;

class Sync extends Base {
	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::SYNC_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'sync' ],
					'args'     => [
						'lockKey'       => self::getStringType(),
						'ateToken'      => self::getStringType(),
						'page'          => self::getIntType(),
						'numberOfPages' => self::getIntType(),
					],
				],
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [
			'manage_options',
			'manage_translations',
			'translate',
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws \Auryn\InjectionException
	 */
	public function sync( WP_REST_Request $request ) {
		$args                    = new Arguments();
		$args->ateToken          = $request->get_param( 'ateToken' );
		$args->page              = $request->get_param( 'nextPage' );
		$args->numberOfPages     = $request->get_param( 'numberOfPages' );
		$args->includeManualAndLongstandingJobs = $request->get_param( 'includeManualAndLongstandingJobs' );

		$lock    = make( SyncLock::class );
		$lockKey = $lock->create( $request->get_param( 'lockKey' ) );
		if ( $lockKey ) {
			$result          = make( Process::class )->run( $args );
			$result->lockKey = $lockKey;

			$this->fallback_to_unstuck_completed_jobs( $result->jobs );
		} else {
			$result = new Result();
		}

		return (array) $result;
	}

	/**
	 * The job was already completed, but for some reason it got into a
	 * different status afterwards. WPML confirms a complete translation
	 * to ATE and then ATE set the job status to "Delivered". So, it's safe
	 * at this point to set the job status to "Completed".
	 *
	 * See wpmldev-2801.
	 *
	 * @param array $jobs
	 */
	private function fallback_to_unstuck_completed_jobs( &$jobs ) {
		if ( ! is_array( $jobs ) ) {
			return;
		}

		foreach ( $jobs as $job ) {
			if (
				! is_object( $job )
				|| ! property_exists( $job, 'jobId' )
				|| ! property_exists( $job, 'ateStatus' )
				|| ! property_exists( $job, 'status' )
			) {
				continue;
			}

			if ( WPML_TM_ATE_Job::ATE_JOB_DELIVERED === $job->ateStatus ) {
				$job->status = ICL_TM_COMPLETE;
				Jobs::setStatus( $job->jobId, ICL_TM_COMPLETE );
			}
		}
	}
}
