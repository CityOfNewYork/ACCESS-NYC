<?php

namespace WPML\Core\BackgroundTask\Service;


use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Command\PersistBackgroundTask;
use WPML\Core\BackgroundTask\Command\UpdateBackgroundTask;
use WPML\Core\BackgroundTask\Exception\TaskIsNotRunnableException;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\Core\BackgroundTask\Repository\BackgroundTaskRepository;
use WPML\Core\WP\App\Resources;
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

	/**
	 * @param BackgroundTaskRepository $backgroundTaskRepository
	 * @param PersistBackgroundTask $persistBackgroundTaskCommand
	 * @param UpdateBackgroundTask $updateBackgroundTaskCommand
	 */
	public function __construct( BackgroundTaskRepository $backgroundTaskRepository, PersistBackgroundTask $persistBackgroundTaskCommand, UpdateBackgroundTask $updateBackgroundTaskCommand ) {
		$this->backgroundTaskRepository     = $backgroundTaskRepository;
		$this->persistBackgroundTaskCommand = $persistBackgroundTaskCommand;
		$this->updateBackgroundTaskCommand = $updateBackgroundTaskCommand;
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
	 * @param TaskEndpointInterface $taskEndpoint
	 * @param Collection $payload
	 *
	 * @return BackgroundTask|null
	 */
	public function add( TaskEndpointInterface $taskEndpoint, Collection $payload ) {
		$itemsCount = $taskEndpoint->getTotalRecords( $payload );
		if ( $itemsCount <= 0 ) {
			return null;
		}
		return $this->persistBackgroundTaskCommand->run(
			$taskEndpoint->getType(),
			BackgroundTask::TASK_STATUS_PENDING,
			$itemsCount,
			$payload->toArray(),
			[]
		);
	}

}
