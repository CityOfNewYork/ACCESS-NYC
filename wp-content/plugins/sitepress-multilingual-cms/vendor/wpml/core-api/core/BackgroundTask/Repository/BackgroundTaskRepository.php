<?php

namespace WPML\Core\BackgroundTask\Repository;

use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\FP\Fns;
use WPML\FP\Obj;

class BackgroundTaskRepository {
	/** @var \wpdb $wpdb */
	protected $wpdb;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param int $task_id
	 *
	 * @return BackgroundTask
	 */
	public function getByTaskId( $task_id ) {
		$table = BackgroundTask::TABLE_NAME;
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->prefix}{$table} WHERE task_id = %s LIMIT 1", $task_id );
		$row   = $this->wpdb->get_row( $query, 'ARRAY_A' );
		$model = $this->createFromQueryResult( $row );

		return $model;
	}

	/**
	 * @param string $task_type
	 *
	 * @return BackgroundTask|null
	 */
	public function getLastIncompletedByType( $task_type ) {
		$table = BackgroundTask::TABLE_NAME;
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->prefix}{$table} WHERE task_type = %s AND task_status != %s LIMIT 1", $task_type, BackgroundTask::TASK_STATUS_COMPLETED );
		$row   = $this->wpdb->get_row( $query, 'ARRAY_A' );
		if ( ! $row ) {
			return null;
		}
		$model = $this->createFromQueryResult( $row );

		return $model;
	}

	/**
	 * @param array $statuses
	 * 
	 * @return BackgroundTask[]
	 */
	public function getAllByTaskStatus( array $statuses ) {
		$fields_in = wpml_prepare_in( $statuses, '%s' );

		$table = $this->wpdb->prefix . BackgroundTask::TABLE_NAME;
		$preparedQuery = $this->wpdb->prepare( "SELECT * FROM {$table} WHERE task_status IN ({$fields_in}) AND 1=%d", 1 );

		return Fns::map(function($row) {
			return $this->createFromQueryResult( $row );
		}, $this->wpdb->get_results( $preparedQuery, 'ARRAY_A' ) );
	}

	/**
	 * @return BackgroundTask[]
	 */
	public function getAllRunnableTasks() {
		return $this->getAllByTaskStatus( [ BackgroundTask::TASK_STATUS_INPROGRESS, BackgroundTask::TASK_STATUS_PENDING, BackgroundTask::TASK_STATUS_PAUSED ] );
	}


	/**
	 * @param array     $statuses
	 * 
	 * @return int
	 */
	private function getCountByTaskStatus( array $statuses ) {
		$fields_in = wpml_prepare_in( $statuses, '%s' );

		$table = $this->wpdb->prefix . BackgroundTask::TABLE_NAME;
		$preparedQuery = $this->wpdb->prepare( "SELECT COUNT(task_id) FROM {$table} WHERE task_status IN ({$fields_in}) AND 1=%d", 1 );
		return (int) $this->wpdb->get_var( $preparedQuery );
	}

	/**
	 * @return int
	 */
	public function getCountRunnableTasks() {
		return $this->getCountByTaskStatus([ BackgroundTask::TASK_STATUS_INPROGRESS, BackgroundTask::TASK_STATUS_PENDING, BackgroundTask::TASK_STATUS_PAUSED ] );
	}


	/**
	 * @param array $data
	 *
	 * @return BackgroundTask
	 */
	public function createFromQueryResult( array $data ) {
		$get = Obj::propOr(null, Fns::__, $data);

		$task = new BackgroundTask();

		$task->setTaskId( $get( 'task_id' ) );
		$task->setTaskType( $get( 'task_type' ) );
		$task->setStatus( $get( 'task_status' ) );
		$task->setStartingDate( isset( $data['starting_date'] ) ? new \DateTime( $data['starting_date'] ) : null );
		$task->setTotalCount( $get( 'total_count' ) );
		$task->setCompletedCount( $get( 'completed_count' ) );
		$task->setCompletedIds( isset( $data['completed_ids'] ) ? unserialize( $data['completed_ids'] ) : null );
		$task->setPayload( isset( $data['payload'] ) ? unserialize( $data['payload'] ) : null );
		$task->setRetryCount( $get( 'retry_count' ) );

		return $task;
	}
}
