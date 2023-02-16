<?php

use WPML\FP\Lst;

class WPML_TM_Job_Entity {

	const POST_TYPE    = 'post';
	const STRING_TYPE  = 'string';
	const STRING_BATCH = 'st-batch_strings';
	const PACKAGE_TYPE = 'package';

	/** @var int */
	private $id;

	/** @var string */
	private $type;

	/** @var int */
	private $tp_id;

	/** @var WPML_TM_Jobs_Batch */
	private $batch;

	/** @var int */
	private $status;

	/** @var int */
	private $original_element_id;

	/** @var string */
	private $source_language;

	/** @var string */
	private $target_language;

	/** @var string */
	private $translation_service;

	/** @var DateTime */
	private $sent_date;

	/** @var DateTime|null */
	private $deadline;

	/** @var int */
	private $translator_id;

	/** @var int */
	private $revision;

	/** @var WPML_TM_Job_TS_Status */
	private $ts_status;

	/** @var bool */
	private $needs_update;

	/** @var bool  */
	private $has_completed_translation = false;

	/** @var string */
	private $title;

	/**
	 * @param int                $id
	 * @param string             $type
	 * @param int                $tp_id
	 * @param WPML_TM_Jobs_Batch $batch
	 * @param int                $status
	 */
	public function __construct( $id, $type, $tp_id, WPML_TM_Jobs_Batch $batch, $status ) {
		$this->id = (int) $id;

		if ( ! self::is_type_valid( $type ) ) {
			throw new InvalidArgumentException( 'Invalid type value: ' . $type );
		}
		$this->type = $type;

		$this->tp_id = (int) $tp_id;
		$this->batch = $batch;

		$this->set_status( $status );
	}

	/**
	 * @deprecated Use `get_rid` instead.
	 *
	 * This method is deprecated because it caused confusion
	 * between the `job_id` and the `rid`.
	 *
	 * It's actually returning the `rid`.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->get_rid();
	}

	/**
	 * @return int
	 */
	public function get_rid() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function get_tp_id() {
		return $this->tp_id;
	}

	/**
	 * @return WPML_TM_Jobs_Batch
	 */
	public function get_batch() {
		return $this->batch;
	}

	/**
	 * @return int
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public function set_status( $status ) {
		$status = (int) $status;
		if ( ! in_array(
			$status,
			array(
				ICL_TM_NOT_TRANSLATED,
				ICL_TM_WAITING_FOR_TRANSLATOR,
				ICL_TM_IN_PROGRESS,
				ICL_TM_TRANSLATION_READY_TO_DOWNLOAD,
				ICL_TM_DUPLICATE,
				ICL_TM_COMPLETE,
				ICL_TM_NEEDS_UPDATE,
				ICL_TM_NEEDS_REVIEW,
				ICL_TM_ATE_CANCELLED,
				ICL_TM_ATE_NEEDS_RETRY,
			),
			true
		) ) {
			$status = ICL_TM_NOT_TRANSLATED;
		}

		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function get_original_element_id() {
		return $this->original_element_id;
	}

	/**
	 * @param int $original_element_id
	 */
	public function set_original_element_id( $original_element_id ) {
		$this->original_element_id = $original_element_id;
	}

	/**
	 * @return string
	 */
	public function get_source_language() {
		return $this->source_language;
	}

	/**
	 * @param string $source_language
	 */
	public function set_source_language( $source_language ) {
		$this->source_language = $source_language;
	}

	/**
	 * @return string
	 */
	public function get_target_language() {
		return $this->target_language;
	}

	/**
	 * @param string $target_language
	 */
	public function set_target_language( $target_language ) {
		$this->target_language = $target_language;
	}

	/**
	 * @return string
	 */
	public function get_translation_service() {
		return $this->translation_service;
	}

	/**
	 * @param string $translation_service
	 */
	public function set_translation_service( $translation_service ) {
		$this->translation_service = $translation_service;
	}

	/**
	 * @return DateTime
	 */
	public function get_sent_date() {
		return $this->sent_date;
	}

	/**
	 * @param DateTime $sent_date
	 */
	public function set_sent_date( DateTime $sent_date ) {
		$this->sent_date = $sent_date;
	}

	/**
	 * @param int $tp_id
	 */
	public function set_tp_id( $tp_id ) {
		$this->tp_id = $tp_id;
	}

	/**
	 * @return DateTime|null
	 */
	public function get_deadline() {
		return $this->deadline;
	}

	/**
	 * @param DateTime|null $deadline
	 */
	public function set_deadline( DateTime $deadline = null ) {
		$this->deadline = $deadline;
	}

	/**
	 * @return int
	 */
	public function get_translator_id() {
		return $this->translator_id;
	}

	/**
	 * @param int $translator_id
	 *
	 * @return self
	 */
	public function set_translator_id( $translator_id ) {
		$this->translator_id = $translator_id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_revision() {
		return $this->revision;
	}

	/**
	 * @param int $revision
	 */
	public function set_revision( $revision ) {
		$this->revision = max( (int) $revision, 1 );
	}

	/**
	 * @return WPML_TM_Job_TS_Status
	 */
	public function get_ts_status() {
		return $this->ts_status;
	}

	/**
	 * @param WPML_TM_Job_TS_Status|string $ts_status
	 */
	public function set_ts_status( $ts_status ) {
		if ( is_string( $ts_status ) ) {
			$status = json_decode( $ts_status );
			if ( $status ) {
				$ts_status = new WPML_TM_Job_TS_Status( $status->ts_status->status, $status->ts_status->links );
			}
		}
		$this->ts_status = $ts_status;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return bool
	 */
	public function is_equal( WPML_TM_Job_Entity $job ) {
		return $this->get_id() === $job->get_id() && $this->get_type() === $job->get_type();
	}

	/**
	 * @return bool
	 */
	public function does_need_update() {
		return $this->needs_update;
	}

	/**
	 * @param bool $needs_update
	 */
	public function set_needs_update( $needs_update ) {
		$this->needs_update = (bool) $needs_update;
	}

	/**
	 * @return bool
	 */
	public function has_completed_translation() {
		return $this->has_completed_translation;
	}

	/**
	 * @param bool $has_completed_translation
	 */
	public function set_has_completed_translation( $has_completed_translation ) {
		$this->has_completed_translation = (bool) $has_completed_translation;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param  string $title
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function is_type_valid( $type ) {
		return Lst::includes( $type, [ self::POST_TYPE, self::STRING_TYPE, self::PACKAGE_TYPE, self::STRING_BATCH ] );
	}
}
