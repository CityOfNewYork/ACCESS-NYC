<?php

namespace WPML\TM\ATE\Download;

use Exception;
use Error;
use WPML\Collect\Support\Collection;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\ATE\API\RequestException;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\EventsTypes;
use WPML_TM_ATE_API;
use function WPML\FP\pipe;

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
					global $iclTranslationManagement;
					$message = 'The translation job could not be applied.';

					if ( $iclTranslationManagement->messages_by_type( 'error' ) ) {
						$stringifyError = pipe(
							Lst::pluck( 'text' ),
							Lst::join( ' ' )
						);

						$message .= ' ' . $stringifyError(
							$iclTranslationManagement->messages_by_type( 'error ')
						);
					}

					throw new Exception( $message );
				}
			} catch ( Exception $e ) {
				$this->logException( $e, $processedJob ?: $job );
			} catch ( Error $e ) {
				$this->logError( $e, $processedJob ?: $job );
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
		$avoidDuplication = false;

		if ( $job ) {
			$entry->ateJobId  = Obj::prop('ateJobId', $job);
			$entry->wpmlJobId = Obj::prop('jobId', $job);
			$entry->extraData = [ 'downloadUrl' => Obj::prop('url', $job) ];
		}

		if ( $e instanceof RequestException ) {
			$entry->eventType = EventsTypes::SERVER_XLIFF;
			if ( $e->getData() ) {
				$entry->extraData += is_array( $e->getData() ) ? $e->getData() : [ $e->getData() ];
			}
			$avoidDuplication = $e->shouldAvoidLogDuplication();
		} else {
			$entry->eventType = EventsTypes::JOB_DOWNLOAD;
		}

		wpml_tm_ate_ams_log( $entry, $avoidDuplication );
	}

	/**
	 * @param Error    $e
	 * @param Job|null $job
	 */
	private function logError( Error $e, $job = null ) {
		$entry              = new Entry();
		$entry->description = sprintf( '%s %s:%s', $e->getMessage(), $e->getFile(), $e->getLine() );

		if ( $job ) {
			$entry->ateJobId  = Obj::prop( 'ateJobId', $job );
			$entry->wpmlJobId = Obj::prop( 'jobId', $job );
			$entry->extraData = [ 'downloadUrl' => Obj::prop( 'url', $job ) ];
		}

		$entry->eventType = EventsTypes::JOB_DOWNLOAD;

		wpml_tm_ate_ams_log( $entry, true );
	}
}
