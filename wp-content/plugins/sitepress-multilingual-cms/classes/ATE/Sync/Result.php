<?php

namespace WPML\TM\ATE\Sync;

class Result {

	/** @var string|false|null $lockKey */
	public $lockKey;

	/** @var string|null $ateToken */
	public $ateToken;

	/** @var int|null $nextPage */
	public $nextPage;

	/** @var int|null $numberOfPages */
	public $numberOfPages;

	/** @var int $downloadQueueSize */
	public $downloadQueueSize = 0;

	/** @var array[wpmlJobId, wpmlStatus, ateStatus, wpmlJobStatus] */
	public $jobs = [];
}
