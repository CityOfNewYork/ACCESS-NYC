<?php

class WPML_TM_Post_Job_Entity extends WPML_TM_Job_Entity {
	/** @var WPML_TM_Job_Element_Entity[]|callable */
	private $elements;

	/** @var int */
	private $translate_job_id;

	/** @var string */
	private $editor;

	/** @var int */
	private $editor_job_id;

	/** @var null|DateTime */
	private $completed_date;

	/** @var bool */
	private $automatic;

	/** @var null|string  */
	private $review_status = null;

	/** @var int */
	private $trid;

	/** @var string */
	private $element_type;

	/** @var string */
	private $job_title;

	public function __construct( $id, $type, $tp_id, $batch, $status, $elements ) {
		parent::__construct( $id, $type, $tp_id, $batch, $status );

		if ( is_callable( $elements ) ) {
			$this->elements = $elements;
		} elseif ( is_array( $elements ) ) {
			foreach ( $elements as $element ) {
				if ( $element instanceof WPML_TM_Job_Element_Entity ) {
					$this->elements[] = $element;
				}
			}
		}
	}

	/**
	 * @return WPML_TM_Job_Element_Entity[]
	 */
	public function get_elements() {
		if ( is_callable( $this->elements ) ) {
			return call_user_func( $this->elements, $this );
		} elseif ( is_array( $this->elements ) ) {
			return $this->elements;
		} else {
			return array();
		}
	}

	/**
	 * @return int
	 */
	public function get_translate_job_id() {
		return $this->translate_job_id;
	}

	/**
	 * @param int $translate_job_id
	 */
	public function set_translate_job_id( $translate_job_id ) {
		$this->translate_job_id = (int) $translate_job_id;
	}

	/**
	 * @return string
	 */
	public function get_editor() {
		return $this->editor;
	}

	/**
	 * @param string $editor
	 */
	public function set_editor( $editor ) {
		$this->editor = (string) $editor;
	}

	/**
	 * @return int
	 */
	public function get_editor_job_id() {
		return $this->editor_job_id;
	}

	/**
	 * @param int $editor_job_id
	 */
	public function set_editor_job_id( $editor_job_id ) {
		$this->editor_job_id = (int) $editor_job_id;
	}

	/**
	 * @return bool
	 */
	public function is_ate_job() {
		return 'local' === $this->get_translation_service() && $this->is_ate_editor();
	}

	/**
	 * @return bool
	 */
	public function is_ate_editor() {
		return WPML_TM_Editors::ATE === $this->get_editor();
	}

	/**
	 * @return DateTime|null
	 */
	public function get_completed_date() {
		return $this->completed_date;
	}

	/**
	 * @param DateTime|null $completed_date
	 */
	public function set_completed_date( DateTime $completed_date = null ) {
		$this->completed_date = $completed_date;
	}

	/**
	 * @return bool
	 */
	public function is_automatic() {
		return $this->automatic;
	}

	/**
	 * @param bool $automatic
	 */
	public function set_automatic( $automatic ) {
		$this->automatic = (bool) $automatic;
	}

	/**
	 * @return string|null
	 */
	public function get_review_status() {
		return $this->review_status;
	}

	/**
	 * @param string|null $review_status
	 */
	public function set_review_status( $review_status ) {
		$this->review_status = $review_status;
	}

	/**
	 * @return int
	 */
	public function get_trid() {
		return $this->trid;
	}

	/**
	 * @param int $trid
	 */
	public function set_trid( $trid ) {
		$this->trid = $trid;
	}

	/**
	 * @return string
	 */
	public function get_element_type() {
		return $this->element_type;
	}

	/**
	 * @param string $element_type
	 */
	public function set_element_type( $element_type ) {
		$this->element_type = $element_type;
	}

	/**
	 * @return string
	 */
	public function get_job_title() {
		return $this->job_title;
	}

	/**
	 * @param string $job_title
	 */
	public function set_job_title( $job_title ) {
		$this->job_title = $job_title;
	}
}
