<?php

namespace WPML\TM\ATE\Download;

use Exception;
use Error;
use WPML\Collect\Support\Collection;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\ATE\API\RequestException;
use WPML\TM\ATE\Jobs;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\EventsTypes;
use WPML\TM\ATE\Review\ReviewStatus;
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
		$appendNeedsReviewAndAutomaticValues = function ( $job ) {
			$data = \WPML\TM\API\Jobs::get( Obj::prop('jobId', $job) );

			$job = Obj::assoc( 'needsReview', ReviewStatus::doesJobNeedReview( $data ), $job );
			$job = Obj::assoc( 'automatic', (bool) Obj::prop( 'automatic', $data ), $job );
			$job = Obj::assoc( 'language_code', Obj::prop( 'language_code', $data ), $job );
			$job = Obj::assoc( 'original_element_id', (int) Obj::prop( 'original_doc_id', $data ), $job );

			return $job;
		};

		$downloadJob = function( $job ) {
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
		};

		$jobs = \wpml_collect( $jobs )->map( $downloadJob )->filter()->values()->map( $appendNeedsReviewAndAutomaticValues );

		$this->acknowledgeAte( $jobs );

		do_action( 'wpml_tm_ate_jobs_downloaded', $jobs );

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
