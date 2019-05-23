<?php

class WPML_TM_Jobs_Search_Params {

	const SCOPE_REMOTE = 'remote';
	const SCOPE_LOCAL  = 'local';
	const SCOPE_ALL    = 'all';

	private $accepted_values = array(
		self::SCOPE_LOCAL,
		self::SCOPE_REMOTE,
		self::SCOPE_ALL,
	);

	/** @var array */
	private $status = array();

	/** @var string */
	private $remote_or_local = self::SCOPE_ALL;

	/** @var array */
	private $job_types = array();

	/** @var int */
	private $local_job_id;

	/** @var int */
	private $limit;

	/** @var int */
	private $offset;

	/** @var string[] */
	private $title;

	/** @var string */
	private $source_language;

	/** @var string[] */
	private $target_language;

	/** @var array */
	private $tp_id = '';

	/** @var WPML_TM_Jobs_Sorting_Param[] */
	private $sorting = array();

	/** @var int */
	private $translated_by;

	/** @var WPML_TM_Jobs_Date_Range */
	private $deadline;

	/** @var WPML_TM_Jobs_Date_Range */
	private $sent;

	/** @var WPML_TM_Jobs_Date_Range */
	private $completed_date;

	/** @var int */
	private $original_element_id;

	public function __construct( array $params = array() ) {
		if ( array_key_exists( 'limit', $params ) ) {
			$this->set_limit( $params['limit'] );
			if ( array_key_exists( 'offset', $params ) ) {
				$this->set_offset( $params['offset'] );
			}
		}

		$fields = array(
			'status',
			'scope',
			'job_types',
			'local_job_id',
			'title',
			'source_language',
			'target_language',
			'sorting',
			'tp_id',
			'translated_by',
			'deadline',
			'completed_date',
			'sent',
			'original_element_id',
		);
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $params ) ) {
				$this->{'set_' . $field}( $params[ $field ] );
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @param array $status
	 *
	 * @return self
	 */
	public function set_status( array $status ) {
		$this->status = array_map( 'intval', array_values( array_filter( $status, 'is_numeric' ) ) );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_scope() {
		return $this->remote_or_local;
	}

	/**
	 * @return array
	 */
	public function get_tp_id() {
		return $this->tp_id;
	}

	/**
	 * @param string $scope
	 *
	 * @retun self
	 */
	public function set_scope( $scope ) {
		if ( ! $this->is_valid_scope( $scope ) ) {
			throw new InvalidArgumentException( 'Invalid scope. Accepted values: ' . implode( ', ',
					$this->get_accepted_values() ) );
		}

		$this->remote_or_local = $scope;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_job_types() {
		return $this->job_types;
	}

	/**
	 * @param int|array $tp_id
	 *
	 * @return $this
	 */
	public function set_tp_id( $tp_id ) {
		$this->tp_id = is_array( $tp_id ) ? $tp_id : array( $tp_id );

		return $this;
	}

	/**
	 * @param string|array $job_types
	 *
	 * @return self
	 */
	public function set_job_types( $job_types ) {
		$correct_types = array(
			WPML_TM_Job_Entity::POST_TYPE,
			WPML_TM_Job_Entity::PACKAGE_TYPE,
			WPML_TM_Job_Entity::STRING_TYPE
		);

		if ( ! is_array( $job_types ) ) {
			$job_types = array( $job_types );
		}

		foreach ( $job_types as $job_type ) {
			if ( ! in_array( $job_type, $correct_types, true ) ) {
				throw new InvalidArgumentException( 'Invalid job type' );
			}
			$this->job_types[] = $job_type;
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_local_job_id() {
		return $this->local_job_id;
	}

	/**
	 * @param int $local_job_id
	 *
	 * @return self
	 */
	public function set_local_job_id( $local_job_id ) {
		$this->local_job_id = (int) $local_job_id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_limit() {
		return $this->limit;
	}

	/**
	 * @param int $limit
	 *
	 * @return self
	 */
	public function set_limit( $limit ) {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_offset() {
		return $this->offset;
	}

	/**
	 * @param int $offset
	 *
	 * @return self
	 */
	public function set_offset( $offset ) {
		$this->offset = $offset;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param array|string $title
	 *
	 * @return self
	 */
	public function set_title( $title ) {
		$this->title = is_array( $title ) ? $title : array( $title );

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_source_language() {
		return $this->source_language;
	}

	/**
	 * @param string $source_language
	 *
	 * @return self
	 */
	public function set_source_language( $source_language ) {
		$this->source_language = $source_language;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function get_target_language() {
		return $this->target_language;
	}

	/**
	 * @param array|string $target_language
	 *
	 * @return self
	 */
	public function set_target_language( $target_language ) {
		$this->target_language = is_array( $target_language ) ? $target_language : array( $target_language );

		return $this;
	}

	/**
	 * @return WPML_TM_Jobs_Sorting_Param[]
	 */
	public function get_sorting() {
		return $this->sorting;
	}

	/**
	 * @param WPML_TM_Jobs_Sorting_Param[] $sorting
	 *
	 * @return self
	 */
	public function set_sorting( array $sorting ) {
		$this->sorting = array();

		foreach ( $sorting as $sorting_param ) {
			if ( $sorting_param instanceof WPML_TM_Jobs_Sorting_Param ) {
				$this->sorting[] = $sorting_param;
			}
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_translated_by() {
		return $this->translated_by;
	}

	/**
	 * @param int|null $translated_by
	 *
	 * @return self
	 */
	public function set_translated_by( $translated_by ) {
		$this->translated_by = (int) $translated_by;

		return $this;
	}

	/**
	 * @return WPML_TM_Jobs_Date_Range
	 */
	public function get_deadline() {
		return $this->deadline;
	}

	/**
	 * @param WPML_TM_Jobs_Date_Range $deadline
	 *
	 * @return self
	 */
	public function set_deadline( WPML_TM_Jobs_Date_Range $deadline ) {
		$this->deadline = $deadline;

		return $this;
	}

	/**
	 * @return WPML_TM_Jobs_Date_Range
	 */
	public function get_sent() {
		return $this->sent;
	}

	/**
	 * @return WPML_TM_Jobs_Date_Range
	 */
	public function get_completed_date() {
		return $this->completed_date;
	}

	/**
	 * @return int
	 */
	public function get_original_element_id() {
		return $this->original_element_id;
	}

	/**
	 * @param WPML_TM_Jobs_Date_Range $sent
	 *
	 * @return self
	 */
	public function set_sent( WPML_TM_Jobs_Date_Range $sent ) {
		$this->sent = $sent;

		return $this;
	}

	/**
	 * @param WPML_TM_Jobs_Date_Range $completed_date
	 *
	 * @return self
	 */
	public function set_completed_date( WPML_TM_Jobs_Date_Range $completed_date ) {
		$this->completed_date = $completed_date;

		return $this;
	}

	/**
	 * @param int $original_element_id
	 *
	 * @return $this
	 */
	public function set_original_element_id( $original_element_id ) {
		$this->original_element_id = $original_element_id;

		return $this;
	}

	private function get_accepted_values() {
		return $this->accepted_values;
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	private function is_valid_scope( $value ) {
		return in_array(
			$value,
			$this->accepted_values,
			true
		);
	}
}
