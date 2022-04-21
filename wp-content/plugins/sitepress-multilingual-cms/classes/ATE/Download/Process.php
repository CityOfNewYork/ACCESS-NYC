<?php

namespace WPML\TM\ATE\Download;

use Exception;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\EventsTypes;
use WPML_TM_ATE_API;

class Process {
	/** @var Consumer $consumer */
	private $consumer;

	/** @var WPML_TM_ATE_API $ateApi */
	private $ateApi;

	public function __construct( Consumer $consumer, WPML_TM_ATE_API $ateApi ) {
		$this->consumer = $consumer;
		$this->ateApi   = $ateApi;
	}

	/**
	 * @param array $jobs
	 *
	 * @return Collection
	 */
	public function run( $jobs ) {
		$jobs = \wpml_collect( $jobs )->map( function ( $job ) {
			$processedJob = null;

			try {
				$processedJob = $this->consumer->process( $job );

				if ( ! $processedJob ) {
					throw new Exception( 'The translation job could not be applied.' );
				}
			} catch ( Exception $e ) {
				$this->logException( $e, $processedJob ?: $job );
			}

			return $processedJob;
		} )->filter()->values();

		$this->acknowledgeAte( $jobs );

		return $jobs;
	}

	private function acknowledgeAte( Collection $processedJobs ) {
		if ( $processedJobs->count() ) {
			$this->ateApi->confirm_received_job( $processedJobs->pluck( 'ateJobId' )->toArray() );
		}
	}

	/**
	 * @param Exception $e
	 * @param Job|null  $job
	 */
	private function logException( Exception $e, $job = null ) {
		$entry              = new Entry();
		$entry->description = $e->getMessage();

		if ( $job ) {
			$entry->ateJobId  = Obj::prop('ateJobId', $job);
			$entry->wpmlJobId = Obj::prop('jobId', $job);
			$entry->extraData = [ 'downloadUrl' => Obj::prop('url', $job) ];
		}

		if ( $e instanceof \Requests_Exception ) {
			$entry->eventType = EventsTypes::SERVER_XLIFF;
		} else {
			$entry->eventType = EventsTypes::JOB_DOWNLOAD;
		}

		wpml_tm_ate_ams_log( $entry );
	}
}
