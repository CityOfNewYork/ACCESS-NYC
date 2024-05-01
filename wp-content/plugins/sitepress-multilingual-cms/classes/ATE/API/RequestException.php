<?php

namespace WPML\TM\ATE\API;

/**
 * Exception for HTTP requests
 */
class RequestException extends \Exception {
	/**
	 * Type of exception
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Data associated with the exception
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * Whether to avoid logging the exception.
	 *
	 * @var bool
	 */
	protected $avoidLogDuplication;

	/**
	 * Create a new exception
	 *
	 * @param string     $message Exception message.
	 * @param string|int $type Exception type.
	 * @param mixed      $data Associated data.
	 * @param int        $code Exception numerical code, if applicable.
	 */
	public function __construct( $message, $type, $data = null, $code = 0, $avoidLogDuplication = false ) {
		// Use type as code if code is not set.
		// This is for backward compatibility.
		$code = 0 === $code ? (int) $type : $code;

		$this->avoidLogDuplication = $avoidLogDuplication;

		parent::__construct( $message, $code );

		$this->type = (string) $type;
		$this->data = $data;
	}

	/**
	 * Like {@see getCode()}, but a string code.
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Gives any relevant data
	 *
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Whether to avoid logging the exception.
	 *
	 * @return bool
	 */
	public function shouldAvoidLogDuplication() {
		return $this->avoidLogDuplication;
	}
}
