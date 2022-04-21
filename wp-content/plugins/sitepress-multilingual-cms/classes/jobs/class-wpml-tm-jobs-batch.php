<?php

class WPML_TM_Jobs_Batch {
	/** @var int */
	private $id;

	/** @var string */
	private $name;

	/** @var int|null */
	private $tp_id;

	/**
	 * @param int      $id
	 * @param string   $name
	 * @param int|null $tp_id
	 */
	public function __construct( $id, $name, $tp_id = null ) {
		$this->id    = (int) $id;
		$this->name  = (string) $name;
		$this->tp_id = $tp_id ? (int) $tp_id : null;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return int|null
	 */
	public function get_tp_id() {
		return $this->tp_id;
	}
}
