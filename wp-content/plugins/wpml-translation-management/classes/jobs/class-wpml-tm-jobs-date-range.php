<?php

class WPML_TM_Jobs_Date_Range {
	/** @var DateTime|null */
	private $begin;

	/** @var DateTime|null */
	private $end;

	/**
	 * Specify how we should treat date values which are NULL
	 *
	 * @var bool
	 */
	private $include_null_date;

	/**
	 * @param DateTime|null $begin
	 * @param DateTime|null $end
	 * @param bool          $include_null_date
	 */
	public function __construct( DateTime $begin = null, DateTime $end = null, $include_null_date = false ) {
		$this->begin             = $begin;
		$this->end               = $end;
		$this->include_null_date = (bool) $include_null_date;
	}

	/**
	 * @return DateTime|null
	 */
	public function get_begin() {
		return $this->begin;
	}

	/**
	 * @return DateTime|null
	 */
	public function get_end() {
		return $this->end;
	}

	/**
	 * @return bool
	 */
	public function is_include_null_date() {
		return $this->include_null_date;
	}
}