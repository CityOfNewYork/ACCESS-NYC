<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml\St;

use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Query\FindByIdQueryInterface;

class WpmlStBeforeRemoveStringsAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_before_remove_strings';
	const ACTION_ARGS = 1;

	/** @var QueueRepositoryInterface */
	private $queueRepository;

	/** @var FindByIdQueryInterface */
	private $findByIdQuery;

	/**
	 * @param QueueRepositoryInterface $queueRepository
	 * @param FindByIdQueryInterface   $findByIdQuery
	 */
	public function __construct(
		QueueRepositoryInterface $queueRepository,
		FindByIdQueryInterface   $findByIdQuery
	) {
		$this->queueRepository = $queueRepository;
		$this->findByIdQuery   = $findByIdQuery;
	}

	protected function onAction( ...$args ) {
		$stringIds = $args[0];
		$strings   = $this->findByIdQuery->execute( $stringIds );
		$this->queueRepository->removeProcessedStrings( $strings );
	}
}