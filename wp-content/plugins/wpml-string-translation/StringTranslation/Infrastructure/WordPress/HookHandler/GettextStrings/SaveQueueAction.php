<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class SaveQueueAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_save_queue';

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		GettextStringsService $gettextStringsService
	) {
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onAction(...$args) {
		$this->gettextStringsService->savePendingStringsQueue();
	}
}
