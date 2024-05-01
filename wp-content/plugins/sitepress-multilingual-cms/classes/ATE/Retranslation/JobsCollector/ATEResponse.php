<?php

namespace WPML\TM\ATE\Retranslation\JobsCollector;

class ATEResponse {

	/**
	 * Job ids in ATE that have to be re-translated.
	 *
	 * @var int[]
	 */
	private $jobIds;

	/**
	 * @var int
	 */
	private $currentPage;

	/**
	 * @var int
	 */
	private $totalPages;

	/**
	 * @var bool
	 */
	private $retranslationFinished;

	/**
	 * @param bool $retranslationFinished
	 * @param int[] $jobIds
	 * @param int $currentPage
	 * @param int $totalPages
	 */
	public function __construct( bool $retranslationFinished, array $jobIds, int $currentPage, int $totalPages ) {
		$this->retranslationFinished = $retranslationFinished;
		$this->jobIds                = $jobIds;
		$this->currentPage           = $currentPage;
		$this->totalPages            = $totalPages;
	}

	/**
	 * @return int[]
	 */
	public function getJobIds() {
		return $this->jobIds;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage() {
		return $this->currentPage;
	}

	/**
	 * @return int
	 */
	public function getTotalPages() {
		return $this->totalPages;
	}

	/**
	 * @return bool
	 */
	public function isRetranslationFinished() {
		return $this->retranslationFinished;
	}
}
