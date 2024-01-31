<?php

namespace WPML\TM\ATE\Retranslation;

class SinglePageBatchHandler {

	const NOT_FINISHED_IN_ATE = 'retranslations-no-finished-in-ate';
	const FINISHED_IN_WPML = 'retranslations-finished-in-wpml';
	const GO_TO_NEXT_PAGE = 'retranslate-next-page';


	/**
	 * @var JobsCollector
	 */
	private $jobsCollector;

	/**
	 * @var RetranslationPreparer
	 */
	private $retranslationPreparer;

	public function __construct( JobsCollector $jobsCollector, RetranslationPreparer $retranslationPreparer ) {
		$this->jobsCollector         = $jobsCollector;
		$this->retranslationPreparer = $retranslationPreparer;
	}

	/**
	 * It tries to handle the Jobs and returns result array with following keys :
	 *
	 * "state" Defines what's the state of the response that we received from ATE, it can be
	 *
	 * NOT_FINISHED_IN_ATE : When ATE didn't finish retranslations yet.
	 *
	 * FINISHED_IN_WPML : When retranslations finished on both ATE and WPML side (no more page to retranslate).
	 *
	 * GO_TO_NEXT_PAGE : When ATE finished retranslations and WPML is trying to finish them page by page.
	 *
	 * "nextPage" When combined with the `state` it defines the value of the next page that should be returned in the AJAX response, it can be
	 *
	 * 0 :  means no more pages to retranslate or ATE didn't finish retranslations yet.
	 *
	 * $nextPage : the number of next page that WPML needs to handle
	 *
	 * @param int $pageNumber
	 *
	 * @return array
	 *
	 */
	public function handle( int $pageNumber = 1 ): array {
		$jobsBatch = $this->jobsCollector->get( $pageNumber );

		if ( ! $jobsBatch->isRetranslationFinished() ) {
			return [ 'state' => self::NOT_FINISHED_IN_ATE, 'nextPage' => 0 ];
		}

		if ( $jobsBatch->getJobIds() ) {
			$this->retranslationPreparer->delegate( $jobsBatch->getJobIds() );
		}

		return $pageNumber >= $jobsBatch->getTotalPages()
			? [ 'state' => self::FINISHED_IN_WPML, 'nextPage' => 0 ]
			: [ 'state' => self::GO_TO_NEXT_PAGE, 'nextPage' => $pageNumber + 1 ];
	}

}
