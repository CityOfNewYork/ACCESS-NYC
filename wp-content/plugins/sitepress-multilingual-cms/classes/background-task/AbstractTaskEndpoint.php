<?php
namespace WPML\BackgroundTask;

use WPML\BackgroundTask\BackgroundTaskLoader;
use WPML\BackgroundTask\BackgroundTaskViewModel;
use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Exception\TaskIsNotRunnableException;
use WPML\Core\BackgroundTask\Model\TaskEndpointInterface;
use WPML\Core\BackgroundTask\Service\BackgroundTaskService;
use WPML\Core\BackgroundTask\Command\UpdateBackgroundTask;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\FP\Either;
use function WPML\Container\make;

abstract class AbstractTaskEndpoint implements TaskEndpointInterface {
	const LOCK_TIME = 2*60;
	const MAX_RETRIES = 0;

	/** @var UpdateBackgroundTask $updateBackgroundTask */
	protected $updateBackgroundTask;

	/** @var BackgroundTaskService $backgroundTaskService */
	protected $backgroundTaskService;

	/**
	 * @param UpdateBackgroundTask $updateBackgroundTask
	 * @param BackgroundTaskService $backgroundTaskService
	 */
	public function __construct( UpdateBackgroundTask $updateBackgroundTask, BackgroundTaskService $backgroundTaskService ) {
		$this->updateBackgroundTask     = $updateBackgroundTask;
		$this->backgroundTaskService = $backgroundTaskService;
	}

	public function isDisplayed() {
		return true;
	}

	public function getLockTime() {
		return static::LOCK_TIME;
	}

	public function getMaxRetries() {
		return static::MAX_RETRIES;
	}

	public function getType() {
		return static::class;
	}

	/**
	 * @param BackgroundTask $task
	 *
	 * @return BackgroundTask
	 */
	abstract function runBackgroundTask( BackgroundTask $task );

	public function run(
		Collection $data
	) {
		try {
			$taskId     = $data['taskId'];
			$task       = $this->backgroundTaskService->startByTaskId( $taskId );
			$task       = $this->runBackgroundTask( $task );

			$this->updateBackgroundTask->runUpdate( $task );

			return $this->getResponse( $task );
		} catch ( TaskIsNotRunnableException $e ) {
			return Either::of( [ 'error' => $e->getMessage() ] );
		}
	}

	/**
	 * @param BackgroundTask $backgroundTask
	 *
	 * @return callable|\WPML\FP\Right
	 */
	private function getResponse( BackgroundTask $backgroundTask ) {
		/** @var \WPML\Utilities\Lock $endpointLock */
		$endpointLock = make( 'WPML\Utilities\Lock', [ ':name' => $backgroundTask->getTaskType() ] );
		$endpointLock->release();

		return Either::of(
			BackgroundTaskViewModel::get( $backgroundTask, true )
		);
	}
}