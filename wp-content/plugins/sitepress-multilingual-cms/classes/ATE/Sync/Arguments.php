<?php

namespace WPML\TM\ATE\Sync;

class Arguments {

	/** @var string|null $lockKey */
	public $lockKey;

	/** @var string|null $ateToken */
	public $ateToken;

	/** @var int|null $page */
	public $page;

	/** @var int|null $numberOfPages */
	public $numberOfPages;

	/** @var boolean $includeManualAndLongstandingJobs */
	public $includeManualAndLongstandingJobs;

}
