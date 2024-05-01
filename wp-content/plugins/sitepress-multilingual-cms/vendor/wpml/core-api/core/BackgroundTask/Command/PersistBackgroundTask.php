<?php

namespace WPML\Core\BackgroundTask\Command;

use \WPML\Core\BackgroundTask\Model\BackgroundTask;

class PersistBackgroundTask {
	/** @var \wpdb */
	private $wpdb;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $task_type
	 * @param $task_status
	 * @param $total_count
	 * @param $completed_ids
	 * @param $payload
	 *
	 * @return BackgroundTask
	 */
	public function run( $task_type, $task_status, $total_count, $payload, $completed_ids ) {
		$model = new BackgroundTask();

		$model->setTaskType( $task_type );
		$model->setStatus( $task_status );
		$model->setTotalCount( $total_count );
		$model->setCompletedIds( $completed_ids );
		$model->setPayload( $payload );

		$this->wpdb->insert(
			$this->wpdb->prefix . BackgroundTask::TABLE_NAME,
			$model->serialize()
		);
		$model->setTaskId( $this->wpdb->insert_id );

		return $model;
	}
}