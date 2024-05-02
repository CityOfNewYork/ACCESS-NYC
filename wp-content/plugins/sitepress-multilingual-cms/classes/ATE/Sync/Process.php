<?php

namespace WPML\TM\ATE\Sync;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Job\Map;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Download\Job;
use WPML\TM\ATE\Log\EventsTypes;
use WPML_TM_ATE_API;
use WPML_TM_ATE_Job_Repository;
use WPML\TM\ATE\Log\Storage;
use WPML\TM\ATE\Log\Entry;
use function WPML\FP\pipe;

class Process {

	const LOCK_RELEASE_TIMEOUT = 1 * MINUTE_IN_SECONDS;

	/** @var WPML_TM_ATE_API $api */
	private $api;

	/** @var WPML_TM_ATE_Job_Repository $ateRepository */
	private $ateRepository;

	public function __construct( WPML_TM_ATE_API $api, WPML_TM_ATE_Job_Repository $ateRepository ) {
		$this->api           = $api;
		$this->ateRepository = $ateRepository;
	}

	/**
	 * @param Arguments $args
	 *
	 * @return Result
	 */
	public function run( Arguments $args ) {
		$result          = new Result();

		if ( $args->page ) {
			$result = $this->runSyncOnPages( $result, $args );
		} else {
			$includeManualAndLongstandingJobs  = (bool) Obj::propOr( true , 'includeManualAndLongstandingJobs', $args);
			$result = $this->runSyncInit( $result, $includeManualAndLongstandingJobs );
		}

		return $result;
	}

	/**
	 * This will run the sync on extra pages.
	 *
	 * @param Result $result
	 * @param Arguments $args
	 *
	 * @return Result
	 */
	private function runSyncOnPages( Result $result, Arguments $args ) {
		$apiPage = $args->page - 1; // ATE API pagination starts at 0.
		$data    = $this->api->sync_page( $args->ateToken, $apiPage );

		$jobs         = Obj::propOr( [], 'items', $data );
		$result->jobs = $this->handleJobs( $jobs );

		if ( !$result->jobs ){
			Storage::add( Entry::createForType(
				EventsTypes::JOBS_SYNC,
				[
					'numberOfPages'     => $args->numberOfPages,
					'page'              => $args->page,
					'downloadQueueSize' => $result->downloadQueueSize,
					'nextPage'          => $result->nextPage,
				]
			) );
		}

		if ( $args->numberOfPages > $args->page ) {
			$result->nextPage      = $args->page + 1;
			$result->numberOfPages = $args->numberOfPages;
			$result->ateToken      = $args->ateToken;
		}

		return $result;
	}

	/**
	 * This will run the first sync iteration.
	 * We send all the job IDs we want to sync.
	 *
	 * @param Result $result
	 * @param boolean $includeManualAndLongstandingJobs
	 *
	 * @return Result
	 */
	private function runSyncInit( Result $result, $includeManualAndLongstandingJobs = true ) {
		$ateJobIds = $this->getAteJobIdsToSync( $includeManualAndLongstandingJobs );


		if ( $ateJobIds ) {
			$this->ateRepository->increment_ate_sync_count( $ateJobIds );
			$data = $this->api->sync_all( $ateJobIds );

			$jobs         = Obj::propOr( [], 'items', $data );
			$result->jobs = $this->handleJobs( $jobs );

			if ( isset( $data->next->pagination_token, $data->next->pages_number ) ) {
				$result->ateToken      = $data->next->pagination_token;
				$result->numberOfPages = $data->next->pages_number;
				$result->nextPage      = 1; // We start pagination at 1 to avoid carrying a falsy value.
			}
		}

		return $result;
	}

	/**
	 * @param boolean $includeManualAndLongstandingJobs
	 *
	 * @return array
	 */
	private function getAteJobIdsToSync( $includeManualAndLongstandingJobs = true ) {
		return $this->ateRepository
			->get_jobs_to_sync( $includeManualAndLongstandingJobs )
			->map_to_property( 'editor_job_id' );
	}

	/**
	 * @param array $items
	 *
	 * @return Job[] $items
	 */
	private function handleJobs( array $items ) {
		$setStatus = function ( $status ) {
			return pipe(
				Fns::tap( Jobs::setStatus( Fns::__, $status ) ),
				Obj::assoc('status', $status)
			);
		};

		$updateStatus = Logic::cond( [
			[
				Relation::propEq( 'status', \WPML_TM_ATE_API::CANCELLED_STATUS ),
				$setStatus( ICL_TM_NOT_TRANSLATED ),
			],
			[
				Relation::propEq( 'status', \WPML_TM_ATE_API::SHOULD_HIDE_STATUS ),
				$setStatus( ICL_TM_ATE_CANCELLED ),
			],
			[
				Fns::always( true ),
				Fns::identity(),
			]
		] );

		return wpml_collect( $items )
			->map( [ Job::class, 'fromAteResponse' ] )
			->map( Obj::over( Obj::lensProp( 'jobId' ), Map::fromRid() ) ) // wpmlJobId returned by ATE endpoint represents RID column in wp_icl_translation_status
			->map( $updateStatus )
			->toArray();
	}
}
