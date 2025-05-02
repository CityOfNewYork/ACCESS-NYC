<?php

namespace WPML\TM\ATE\Retranslation;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\TM\ATE\SyncLock;

class Endpoint implements IHandler {
	/** @var SinglePageBatchHandler */
	private $singlePageBatchHandler;

	/** @var Scheduler */
	private $scheduler;

	/** @var SyncLock */
	private $syncLock;

	public function __construct( SinglePageBatchHandler $singlePageBatchHandler, Scheduler $scheduler, SyncLock $syncLock ) {
		$this->singlePageBatchHandler = $singlePageBatchHandler;
		$this->scheduler              = $scheduler;
		$this->syncLock               = $syncLock;
	}

	/**
	 * @param Collection $data
	 *
	 * @return Right<{lockKey: bool|string, nextPage: int}>|Left<{lockKey: bool|string, nextPage: int}> it returns next page number or 0 if there are no more pages
	 */
	public function run( Collection $data ) {
		/**
		 * @see wpmldev-2748
		 * The first call is done immediately after the Update translation button is clicked in the ATE Tools page.
		 * For sure, ATE process is not completed yet, so there is no sense to call their API yet.
		 * Instead of that, we schedule the next check in the future.
		 */
		if ( $data->get( 'firstSchedule', false ) ) {
			$this->scheduler->scheduleNextRun();

			return Either::of( [] );
		}

		$lockKey = $this->syncLock->create( $data->get( 'lockKey' ) );
		if ( ! $lockKey ) {
			$this->scheduler->scheduleNextRun();
			return Either::left( [ 'lockKey' => false, 'nextPage' => 0 ] );
		}

		$page = $data->get( 'page', 1 );

		$singlePageBatchHandlerResult = $this->singlePageBatchHandler->handle( $page );

		switch ( $singlePageBatchHandlerResult['state'] ) {
			case SinglePageBatchHandler::NOT_FINISHED_IN_ATE:
				$this->scheduler->scheduleNextRun();
				$this->syncLock->release();
				$lockKey = false;
				break;
			case SinglePageBatchHandler::FINISHED_IN_WPML:
				$this->scheduler->disable();
				$this->syncLock->release();
				$lockKey = false;
				break;
		}

		return Either::of( [ 'lockKey' => $lockKey, 'nextPage' => $singlePageBatchHandlerResult['nextPage'] ] );
	}
}
