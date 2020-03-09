<?php

namespace WPML\ST\MO\Generate\Process;


use WPML\Utils\Pager;
use WPML\ST\MO\Generate\MultiSite\Executor;

class MultiSiteProcess implements Process {
	/** @var Executor */
	private $multiSiteExecutor;

	/** @var SingleSiteProcess */
	private $singleSiteProcess;

	/** @var Status */
	private $status;

	/** @var Pager */
	private $pager;

	/** @var SubSiteValidator */
	private $subSiteValidator;

	/**
	 * @param Executor          $multiSiteExecutor
	 * @param SingleSiteProcess $singleSiteProcess
	 * @param Status            $status
	 * @param Pager             $pager
	 * @param SubSiteValidator  $subSiteValidator
	 */
	public function __construct(
		Executor $multiSiteExecutor,
		SingleSiteProcess $singleSiteProcess,
		Status $status,
		Pager $pager,
		SubSiteValidator $subSiteValidator
	) {
		$this->multiSiteExecutor = $multiSiteExecutor;
		$this->singleSiteProcess = $singleSiteProcess;
		$this->status            = $status;
		$this->pager             = $pager;
		$this->subSiteValidator  = $subSiteValidator;
	}


	public function runAll() {
		$this->multiSiteExecutor->withEach( $this->runIfSetupComplete( [ $this->singleSiteProcess, 'runAll' ] ) );
		$this->status->markComplete( true );
	}

	/**
	 * @return int Is completed
	 */
	public function runPage() {
		$remaining = $this->pager->iterate( $this->multiSiteExecutor->getSiteIds(), function ( $siteId ) {
			return $this->multiSiteExecutor->executeWith(
				$siteId,
				$this->runIfSetupComplete( function () {
					// no more remaining pages which means that process is done
					return $this->singleSiteProcess->runPage() === 0;
				} )
			);
		} );

		if ( $remaining === 0 ) {
			$this->multiSiteExecutor->executeWith( Executor::MAIN_SITE_ID, function () {
				$this->status->markComplete( true );
			} );
		}

		return $remaining;
	}

	/**
	 * @return int
	 */
	public function getPagesCount() {
		$isCompletedForAllSites = $this->multiSiteExecutor->executeWith(
			Executor::MAIN_SITE_ID,
			[ $this->status, 'isCompleteForAllSites' ]
		);
		if ( $isCompletedForAllSites ) {
			return 0;
		}

		return $this->multiSiteExecutor->getSiteIds()->count();
	}

	/**
	 * @return bool
	 */
	public function isCompleted() {
		return $this->getPagesCount() === 0;
	}


	private function runIfSetupComplete( $callback ) {
		return function () use ( $callback ) {
			if ( $this->subSiteValidator->isValid() ) {
				return $callback();
			}

			return true;
		};
	}
}