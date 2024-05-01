<?php

namespace WPML\BackgroundTask;

use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Command\UpdateBackgroundTask;
use WPML\Core\BackgroundTask\Repository\BackgroundTaskRepository;
use WPML\Core\WP\App\Resources;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;

class BackgroundTaskLoader implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var UpdateBackgroundTask $updateBackgroundTaskCommand */
	private $updateBackgroundTaskCommand;

	/** @var BackgroundTaskRepository $backgroundTaskRepository */
	private $backgroundTaskRepository;

	/**
	 * @param UpdateBackgroundTask          $updateBackgroundTaskCommand
	 * @param BackgroundTaskRepository      $backgroundTaskRepository
	 */
	public function __construct(
		UpdateBackgroundTask $updateBackgroundTaskCommand,
		BackgroundTaskRepository $backgroundTaskRepository
	) {
		$this->updateBackgroundTaskCommand     = $updateBackgroundTaskCommand;
		$this->backgroundTaskRepository = $backgroundTaskRepository;
	}


	public function add_hooks() {
		Hooks::onAction( 'wp_loaded' )
		     ->then( function() {
			     $tasks = $this->getSerializedTasks();
			     Resources::enqueueGlobalVariable('wpml_background_tasks', [
					 /** @phpstan-ignore-next-line */
				     'endpoints' => array_merge( Lst::pluck('taskType', $tasks), [ BackgroundTaskLoader::class ] ),
				     'tasks' => $tasks,
			     ] );
		     } );
	}

	/**
	 * @param \WPML\Collect\Support\Collection $data
	 */
	public function run(
		Collection $data
	) {
		$taskId = $data['taskId'];
		$cmd    = $data['cmd'];

		$task = $this->backgroundTaskRepository->getByTaskId( $taskId );

		if ( 'stop' === $cmd ) {
			$this->updateBackgroundTaskCommand->runStop( $task );
		} elseif ( 'pause' === $cmd ) {
			$this->updateBackgroundTaskCommand->saveStatusPaused( $task );
		} elseif ( 'resume' === $cmd ) {
			$this->updateBackgroundTaskCommand->saveStatusResumed( $task );
		} elseif ( 'restart' === $cmd ) {
			$this->updateBackgroundTaskCommand->saveStatusRestart( $task );
		}

		$taskData = BackgroundTaskViewModel::get( $task );

		return Either::of( $taskData );
	}


	/**
	 * @return array
	 */
	public function getSerializedTasks() {
		return Fns::map(
			function( $task ) {
				return BackgroundTaskViewModel::get( $task, true );
			},
			$this->backgroundTaskRepository->getAllRunnableTasks()
		);
	}


}
