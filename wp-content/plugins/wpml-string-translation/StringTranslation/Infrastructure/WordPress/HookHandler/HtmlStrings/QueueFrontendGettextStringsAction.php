<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings;

use WPML\StringTranslation\Application\StringHtml\Service\HtmlStringsService;
use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsRepositoryInterface;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class QueueFrontendGettextStringsAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_queue_frontend_gettext_strings';
	const ACTION_ARGS = 1;

	/** @var HtmlStringsService */
	private $htmlStringsService;

	/** @var HtmlStringsRepositoryInterface */
	private $htmlStringsRepository;

	public function __construct(
		HtmlStringsService $htmlStringsService,
		HtmlStringsRepositoryInterface $htmlStringsRepository
	) {
		$this->htmlStringsService = $htmlStringsService;
		$this->htmlStringsRepository = $htmlStringsRepository;
	}

	protected function onAction(...$args) {
		$html = $args[0];
		$this->htmlStringsService->queueGettextStringsEqualToHtmlStringsAsFrontend(
			$this->htmlStringsRepository->getAllStringsFromHtml(
				$html
			)
		);
	}
}
