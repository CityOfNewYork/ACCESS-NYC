<?php

class WPML_TP_Job_Status {
	/** @var int */
	private $tp_id;

	/** @var int */
	private $batch_id;

	/** @var string */
	private $status;

	/** @var int */
	private $revision;

	/** @var  WPML_TM_Job_TS_Status|null */
	private $ts_status;

	/**
	 * @param int                        $tp_id
	 * @param int                        $batch_id
	 * @param string                     $state
	 * @param WPML_TM_Job_TS_Status|null $ts_status
	 * @param int                        $revision
	 */
	public function __construct( $tp_id, $batch_id, $state, $revision = 1, $ts_status = null ) {
		$this->tp_id    = (int) $tp_id;
		$this->batch_id = (int) $batch_id;

		if ( ! in_array( $state, WPML_TP_Job_States::get_possible_states(), true ) ) {
			$state = 'any';
		}
		$this->status    = $state;
		$this->revision  = (int) $revision;
		$this->ts_status = $ts_status;
	}

	/**
	 * @return int
	 */
	public function get_tp_id() {
		return $this->tp_id;
	}

	/**
	 * @return int
	 */
	public function get_batch_id() {
		return $this->batch_id;
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return int
	 */
	public function get_revision() {
		return $this->revision;
	}

	/**
	 * @return WPML_TM_Job_TS_Status|null
	 */
	public function get_ts_status() {
		return $this->ts_status;
	}

}