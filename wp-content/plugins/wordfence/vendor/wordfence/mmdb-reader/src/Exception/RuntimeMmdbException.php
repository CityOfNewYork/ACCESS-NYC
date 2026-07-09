<?php

namespace Wordfence\MmdbReader\Exception;

use RuntimeException;
use Throwable;

class RuntimeMmdbException extends RuntimeException implements MmdbThrowable {

	/**
	 * @param string $message
	 * @param ?Throwable $previous
	 */
	public function __construct($message, $previous = null) {
		parent::__construct($message, 0, $previous);
	}

}