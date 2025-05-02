<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings;

use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class UnloadQueueAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_unload_queue';

	/** @var QueueRepositoryInterface */
	private $queueRepository;

	public function __construct(
		QueueRepositoryInterface $queueRepository
	) {
		$this->queueRepository = $queueRepository;
	}

	protected function onAction(...$args) {
		$this->queueRepository->unloadStrings();
	}
}
