<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings;

use WPML\StringTranslation\Application\StringHtml\Service\HtmlStringsService;
use WPML\StringTranslation\Application\StringHtml\Repository\JsonStringsRepositoryInterface;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class QueueJsonFrontendGettextStringsAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_queue_json_frontend_gettext_strings';
	const ACTION_ARGS = 1;

	/** @var HtmlStringsService */
	private $htmlStringsService;

	/** @var JsonStringsRepositoryInterface */
	private $jsonStringsRepository;

	public function __construct(
		HtmlStringsService $htmlStringsService,
		JsonStringsRepositoryInterface $jsonStringsRepository
	) {
		$this->htmlStringsService    = $htmlStringsService;
		$this->jsonStringsRepository = $jsonStringsRepository;
	}

	protected function onAction(...$args) {
		$json = $args[0];
		$this->htmlStringsService->queueGettextStringsEqualToHtmlStringsAsFrontend(
			$this->jsonStringsRepository->getAllStringsFromOutput( (string) $json )
		);
	}
}
