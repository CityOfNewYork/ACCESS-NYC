<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings;

use WPML\StringTranslation\Application\StringCore\Service\StringsService;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class ProcessQueueAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_process_queue';

	/** @var StringsService */
	private $stringsService;

	public function __construct(
		StringsService $stringsService
	) {
		$this->stringsService = $stringsService;
	}

	protected function onAction(...$args) {
		$this->stringsService->maybeProcessQueue();
	}
}
