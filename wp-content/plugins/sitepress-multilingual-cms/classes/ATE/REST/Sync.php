<?php

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\Collect\Support\Collection;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\Rest\Adaptor;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Download\Job;
use WPML\TM\ATE\Review\PreviewLink;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Review\StatusIcons;
use WPML\TM\ATE\Sync\Arguments;
use WPML\TM\ATE\Sync\Factory;
use WPML\TM\ATE\Sync\Process;
use WPML\TM\ATE\Sync\Result;
use WPML\TM\ATE\SyncLock;
use WPML\TM\REST\Base;
use WPML\Utilities\KeyedLock;
use WPML_TM_ATE_AMS_Endpoints;
use function WPML\Container\make;
use function WPML\FP\pipe;

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

			$jobsFromDB = Fns::filter(
				Logic::complement( $this->findSyncedJob( $result->jobs ) ),
				$this->getJobStatuses( $request->get_param( 'jobIds' ), $request->get_param( 'returnUrl' ) )
			);
			$result     = $this->createResultWithJobs( Lst::concat( $result->jobs, $jobsFromDB ), $result );
		} else {
			$result = $this->createResultWithJobs( $this->getJobStatuses( $request->get_param( 'jobIds' ), $request->get_param( 'returnUrl' ) ) );
		}

		return (array) $result;
	}

	private function getJobStatuses( $wpmlJobIds, $returnUrl ) {
		if ( ! $wpmlJobIds ) {
			return [];
		}

		global $wpdb;

		$ids = wpml_prepare_in( $wpmlJobIds, '%d' );
		$sql = "
			SELECT jobs.job_id as jobId, statuses.status as status, jobs.editor_job_id as ateJobId FROM {$wpdb->prefix}icl_translate_job as jobs 
		    INNER JOIN {$wpdb->prefix}icl_translation_status as statuses ON statuses.rid = jobs.rid
			WHERE jobs.job_id IN ( {$ids} ) AND 1 = %d
	    "; // I need additional AND condition to utilize prepare function  which is required to make writing unit tests easier. It's not perfect but saves a lot of time now

		$result = $wpdb->get_results( $wpdb->prepare( $sql , 1) );
		if ( ! is_array( $result ) ) {
			return [];
		}

		$jobs = Fns::map( Obj::evolve( [
			'jobId'     => Cast::toInt(),
			'status' => Cast::toInt(),
			'ateJobId'      => Cast::toInt(),
		] ), $result );

		list( $completed, $notCompleted ) = \wpml_collect( $jobs )->partition( Relation::propEq( 'status', ICL_TM_COMPLETE ) );

		if ( count( $completed ) ) {
			$completed = Download::getJobs( $completed, $returnUrl )->map( function ( $job ) {
				return (array) $job;
			} );
		}

		return $completed->merge( $notCompleted )->all();
	}

	private function findSyncedJob( $jobsFromATE ) {
		return function ( $jobFromDb ) use ( $jobsFromATE ) {
			return Lst::find( Relation::propEq( 'jobId', Obj::prop( 'jobId', $jobFromDb ) ), $jobsFromATE );
		};
	}

	/**
	 * @param array $jobs
	 * @param Result|null $template
	 *
	 * @return Result
	 */
	private function createResultWithJobs( array $jobs, Result $template = null ) {
		$result       = $template ? clone $template : new Result();
		$result->jobs = $jobs;

		return $result;
	}
}
