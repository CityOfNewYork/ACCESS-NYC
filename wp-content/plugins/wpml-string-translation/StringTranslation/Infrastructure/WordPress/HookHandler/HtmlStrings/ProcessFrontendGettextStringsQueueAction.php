<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings;

use WPML\StringTranslation\Application\StringHtml\Service\HtmlStringsService;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class ProcessFrontendGettextStringsQueueAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_process_frontend_gettext_strings_queue';

	/** @var HtmlStringsService */
	private $htmlStringsService;

	public function __construct(
		HtmlStringsService $htmlStringsService
	) {
		$this->htmlStringsService = $htmlStringsService;
	}

	protected function onAction(...$args) {
		$this->htmlStringsService->maybeProcessFrontendGettextStringsQueue();
	}
}
