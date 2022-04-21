<?php

class WPML_TM_Job_Element_Entity {
	/** @var int */
	private $id;

	/** @var int */
	private $content_id;

	/** @var int */
	private $timestamp;

	/** @var string */
	private $type;

	/** @var string */
	private $format;

	/** @var bool */
	private $translatable;

	/** @var string */
	private $data;

	/** @var string */
	private $data_translated;

	/** @var bool */
	private $finished;

	/**
	 * @param int    $id
	 * @param int    $content_id
	 * @param int    $timestamp
	 * @param string $type
	 * @param string $format
	 * @param bool   $is_translatable
	 * @param string $data
	 * @param string $data_translated
	 * @param bool   $finished
	 */
	public function __construct(
		$id,
		$content_id,
		$timestamp,
		$type,
		$format,
		$is_translatable,
		$data,
		$data_translated,
		$finished
	) {
		$this->id              = (int) $id;
		$this->content_id      = (int) $content_id;
		$this->timestamp       = (int) $timestamp;
		$this->type            = (string) $type;
		$this->format          = (string) $format;
		$this->translatable    = (bool) $is_translatable;
		$this->data            = (string) $data;
		$this->data_translated = (bool) $data_translated;
		$this->finished        = (bool) $finished;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get_content_id() {
		return $this->content_id;
	}

	/**
	 * @return int
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function get_format() {
		return $this->format;
	}

	/**
	 * @return bool
	 */
	public function is_translatable() {
		return $this->translatable;
	}

	/**
	 * @return string
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function get_data_translated() {
		return $this->data_translated;
	}

	/**
	 * @return bool
	 */
	public function is_finished() {
		return $this->finished;
	}
}
