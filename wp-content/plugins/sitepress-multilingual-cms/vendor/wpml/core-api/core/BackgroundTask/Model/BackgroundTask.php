<?php

namespace WPML\Core\BackgroundTask\Model;

use WPML\FP\Obj;

/**
 * Class BackgroundTask
 *
 * @author OnTheGoSystems
 */
class BackgroundTask {
	const TABLE_NAME = 'icl_background_task';

	const ITEMS_COUNT_IN_TASK = 10;

	const TASK_TYPE_DEFAULT = 'Default';
	const TASK_TYPE_PROCESS_NEW_TRANSLATABLE_FIELDS = 'ProcessNewTranslatableFields';

	const TASK_STATUS_PENDING = 0;
	const TASK_STATUS_INPROGRESS = 1;
	const TASK_STATUS_PAUSED = 2;
	const TASK_STATUS_COMPLETED = 3;

	//const EXPIRE_AFTER_SECONDS = 2 * MINUTE_IN_SECONDS;
	const EXPIRE_AFTER_SECONDS = 3600;
	const MAX_RETRY_COUNT = 2;

	/** @var int|null $taskId */
	private $taskId;

	/** @var string $taskType */
	private $taskType = self::TASK_TYPE_DEFAULT;

	/** @var int $taskStatus */
	private $taskStatus = self::TASK_STATUS_PENDING;

	/** @var DateTime|null $startingDate */
	private $startingDate;

	/** @var int $totalCount */
	private $totalCount = 0;

	/** @var int $completedCount */
	private $completedCount = 0;

	/** @var array|null $completedIds */
	private $completedIds = [];

	/** @var array $payload */
	private $payload = [];

	/** @var int $retryCount */
	private $retryCount = 0;

	/** @var int $itemsCountInTask */
	private static $itemsCountInTask = self::ITEMS_COUNT_IN_TASK;

	/**
	 * @return array
	 */
	public function serialize() {
		return [
			'task_type'       => $this->getTaskType(),
			'task_status'     => $this->getStatus(),
			'starting_date'   => ( $this->hasStartingDate() ) ? $this->getStartingDate()->format('Y-m-d H:i:s') : null,
			'total_count'     => $this->getTotalCount(),
			'completed_count' => $this->getCompletedCount(),
			'completed_ids'   => ( $this->getCompletedIds() ) ? serialize( $this->getCompletedIds() ) : null,
			'payload'         => serialize( $this->getPayload() ),
			'retry_count'     => $this->getRetryCount(),
		];
	}

	public function finish() {
		$this->taskStatus     = static::TASK_STATUS_COMPLETED;
		$this->completedCount = $this->totalCount;
	}

	/**
	 * @param int|string|null $taskId
	 */
	public function setTaskId( $taskId ) {
		$this->taskId = ( is_numeric( $taskId ) ) ? (int) $taskId : null;
	}

	/**
	 * @return int|null
	 */
	public function getTaskId() {
		return $this->taskId;
	}

	/**
	 * @param string|null
	 */
	public function setTaskType( $taskType ) {
		$this->taskType = ( is_string( $taskType ) ) ? $taskType : self::TASK_TYPE_DEFAULT;
	}

	/**
	 * @return string
	 */
	public function getTaskType() {
		return $this->taskType;
	}


	/**
	 * @param int|null $status
	 */
	public function setStatus( $status ) {
		$this->taskStatus = ( is_numeric( $status ) ) ? (int) $status : self::TASK_STATUS_PENDING;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return (int) $this->taskStatus;
	}

	/**
	 * @return bool
	 */
	public function isStatusPending() {
		return ( self::TASK_STATUS_PENDING === $this->getStatus() );
	}

	/**
	 * @return bool
	 */
	public function isStatusInProgress() {
		return ( self::TASK_STATUS_INPROGRESS === $this->getStatus() );
	}

	/**
	 * @return bool
	 */
	public function isStatusPaused() {
		return ( self::TASK_STATUS_PAUSED === $this->getStatus() );
	}

	/**
	 * @return bool
	 */
	public function isStatusCompleted() {
		return ( self::TASK_STATUS_COMPLETED === $this->getStatus() );
	}

	/**
	 * @return string
	 */
	public function getStatusName() {
		if ( $this->isStatusPending() ) {
			return self::TASK_STATUS_PENDING;
		}
		if ( $this->isStatusInProgress() ) {
			return self::TASK_STATUS_INPROGRESS;
		}
		if ( $this->isStatusPaused() ) {
			return self::TASK_STATUS_PAUSED;
		}
		if ( $this->isStatusCompleted() ) {
			return self::TASK_STATUS_COMPLETED;
		}
	}

	/**
	 * @param \DateTime|null $startingDate
	 */
	public function setStartingDate( $startingDate ) {
		$this->startingDate = $startingDate;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getStartingDate() {
		return $this->startingDate;
	}

	/**
	 * @return bool
	 */
	public function hasStartingDate() {
		return ( $this->getStartingDate() instanceof \DateTime );
	}

	/**
	 * @param int|string|null $totalCount
	 */
	public function setTotalCount( $totalCount ) {
		$this->totalCount = ( is_numeric( $totalCount ) ) ? (int) $totalCount : 0;
	}

	/**
	 * @return int
	 */
	public function getTotalCount() {
		return ( is_numeric( $this->totalCount ) ) ? (int) $this->totalCount : $this->totalCount;
	}

	/**
	 * @param int $completed_count
	 */
	public function addCompletedCount( $completed_count ) {
		$this->completedCount += $completed_count;
	}

	/**
	 * @return int
	 */
	public function getCompletedCount() {
		return ( is_numeric( $this->completedCount ) ) ? (int) $this->completedCount : $this->completedCount;
	}

	/**
	 * @param int|string|null $completedCount
	 */
	public function setCompletedCount( $completedCount ) {
		$this->completedCount = ( is_numeric( $completedCount ) ) ? $completedCount : 0;
	}

	/**
	 * @param array $completed_ids
	 */
	public function addCompletedIds( $completed_ids ) {
		$this->completedIds = array_merge(
			$this->getCompletedIds(),
			$completed_ids
		);
	}

	/**
	 * @return array|null
	 */
	public function getCompletedIds() {
		return $this->completedIds;
	}

	/**
	 * @param array|null $completedIds
	 */
	public function setCompletedIds( $completedIds ) {
		$this->completedIds = $completedIds;
	}

	/**
	 * @param bool
	 */
	public function hasCompletedIds() {
		return ( ! is_null( $this->completedIds ) );
	}

	/**
	 * @param array|null $payload
	 */
	public function setPayload( $payload ) {
		$this->payload = ( is_array( $payload ) ) ? $payload : [];
	}

	/**
	 * @return array
	 */
	public function getPayload() {
		return $this->payload;
	}


	/**
	 * @param int|string|null $retryCount
	 */
	public function setRetryCount( $retryCount ) {
		$this->retryCount = ( is_numeric( $retryCount ) ) ? (int) $retryCount : 0;
	}

	/**
	 * @return int
	 */
	public function getRetryCount() {
		return (int) $this->retryCount;
	}
}
