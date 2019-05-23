<?php

class WPML_TM_Jobs_Batch {
	/** @var int */
	private $id;

	/** @var int|null */
	private $tp_id;

	/**
	 * @param int      $id
	 * @param int|null $tp_id
	 */
	public function __construct( $id, $tp_id = null ) {
		$this->id    = (int) $id;
		$this->tp_id = $tp_id ? (int) $tp_id : null;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return int|null
	 */
	public function get_tp_id() {
		return $this->tp_id;
	}
}