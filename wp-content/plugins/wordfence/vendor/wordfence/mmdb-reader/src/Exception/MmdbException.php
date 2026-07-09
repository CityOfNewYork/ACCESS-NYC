<?php

namespace Wordfence\MmdbReader\Exception;

use Exception;
use Throwable;

class MmdbException extends Exception implements MmdbThrowable {

	/**
	 * @param string $message
	 * @param ?Throwable $previous
	 */
	public function __construct($message, $previous = null) {
		parent::__construct($message, 0, $previous);
	}

}