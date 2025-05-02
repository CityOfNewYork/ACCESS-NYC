<?php

namespace WPML\Core\BackgroundTask\Service;


use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Command\PersistBackgroundTask;
use WPML\Core\BackgroundTask\Command\UpdateBackgroundTask;
use WPML\Core\BackgroundTask\Command\DeleteBackgroundTask;
use WPML\Core\BackgroundTask\Exception\TaskIsNotRunnableException;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\Core\BackgroundTask\Repository\BackgroundTaskRepository;
use WPML\Core\BackgroundTask\Model\TaskEndpointInterface;
use function WPML\Container\make;

/**
 * Class BackgroundTaskService
 *
 * @package WPML\Core
 *
 * Class to add background ajax tasks.
 * Call the `add` function with the class name of the endpoint and any data that the end point requires.
 */
class BackgroundTaskService {

	/** @var BackgroundTaskRepository $backgroundTaskRepository */
	private $backgroundTaskRepository;

	/** @var PersistBackgroundTask $persistBackgroundTaskCommand */
	private $persistBackgroundTaskCommand;

	/** @var UpdateBackgroundTask $updateBackgroundTaskCommand */
	private $updateBackgroundTaskCommand;

	/** @var DeleteBackgroundTask $deleteBackgroundTaskCommand */
	private $deleteBackgroundTaskCommand;

	/**
	 * @param BackgroundTaskRepository $backgroundTaskRepository
	 * @param PersistBackgroundTask $persistBackgroundTaskCommand
	 * @param UpdateBackgroundTask $updateBackgroundTaskCommand
	 * @param DeleteBackgroundTask $deleteBackgroundTaskCommand
	 */
	public function __construct(
		BackgroundTaskRepository $backgroundTaskRepository,
		PersistBackgroundTask $persistBackgroundTaskCommand,
		UpdateBackgroundTask $updateBackgroundTaskCommand,
		DeleteBackgroundTask $deleteBackgroundTaskCommand
	) {
		$this->backgroundTaskRepository     = $backgroundTaskRepository;
		$this->persistBackgroundTaskCommand = $persistBackgroundTaskCommand;
		$this->updateBackgroundTaskCommand = $updateBackgroundTaskCommand;
		$this->deleteBackgroundTaskCommand = $deleteBackgroundTaskCommand;
	}

	/**
	 * @param TaskEndpointInterface $taskEndpoint
	 * @param Collection $payload
	 *
	 * @throws TaskIsNotRunnableException
	 * @return BackgroundTask|null
	 */
	public function startByTaskId( $taskId ) {
		$task     = $this->backgroundTaskRepository->getByTaskId( $taskId );
		$taskEndpoint = make( $task->getTaskType() );
		if ( ! $taskEndpoint instanceof TaskEndpointInterface ) {
			throw new TaskIsNotRunnableException();
		}
		$task = $this->updateBackgroundTaskCommand->startTask( $task, $taskEndpoint );

		return $task;
	}

	/**
	 * Adds a unique background task of the given task type.
	 *
	 * If there is already a task of the same type the existing task will be returned.
	 * This does NOT reset the completed items.
	 *
	 * @param TaskEndpointInterface $taskEndpoint
	 * @param Collection $payload
	 *
	 * @return BackgroundTask|null
	 */
	public function addOnce( TaskEndpointInterface $taskEndpoint, Collection $payload ) {
		$backgroundTask = $this->backgroundTaskRepository->getLastIncompletedByType( $taskEndpoint->getType() );
		if ( null === $backgroundTask ) {
			$backgroundTask = $this->add( $taskEndpoint, $payload );
		}

		return $backgroundTask;
	}

	/**
	 * Adds a new background task.
	 *
	 * If there is already a task of the same type and with the same payload,
	 * it will just reset the completed items and return the task.
	 *
	 * If only one task of the same type is needed (independent of the payload)
	 * use `addOnce` instead.
	 *
	 * @param TaskEndpointInterface $taskEndpoint
	 * @param Collection $payload
	 * @param bool $force
	 *
	 * @return BackgroundTask|null
	 */
	public function add( TaskEndpointInterface $taskEndpoint, Collection $payload ) {
		$payloadArray = $payload->toArray();

		$backgroundTask = $this->backgroundTaskRepository->getLastIncompletedByType(
			$taskEndpoint->getType(),
			$payloadArray
		);

		if ( $backgroundTask ) {
		  // Just reset all completed items, so it starts over for all items.
		  // Use case: A field update is in progress and is updated again. In
		  // this case it's required to start again.
		  $backgroundTask->setCompletedCount( 0 );
		  $backgroundTask->setCompletedIds( null );
		  $this->updateBackgroundTaskCommand->runUpdate( $backgroundTask );
		  return $backgroundTask;
		}

		$itemsCount = $taskEndpoint->getTotalRecords( $payload );
		if ( $itemsCount <= 0 ) {
			return null;
		}
		return $this->persistBackgroundTaskCommand->run(
			$taskEndpoint->getType(),
			BackgroundTask::TASK_STATUS_PENDING,
			$itemsCount,
			$payloadArray,
			[]
		);
	}

	/**
	 * Deletes a background task.
	 *
	 * @param BackgroundTask $task
	 */
	public function delete( $taskId ) {
		$this->deleteBackgroundTaskCommand->run( $taskId );
	}

}
