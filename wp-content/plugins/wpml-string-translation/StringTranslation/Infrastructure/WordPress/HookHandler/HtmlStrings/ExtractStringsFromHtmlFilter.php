<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\HtmlStrings;

use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsRepositoryInterface;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractFilterHookHandler;

class ExtractHtmlStringsFilter extends AbstractFilterHookHandler {
	const FILTER_NAME = 'wpml_st_extract_html_strings';
	const FILTER_ARGS = 1;

	/** @var HtmlStringsRepositoryInterface */
	private $htmlStringsRepository;

	public function __construct(
		HtmlStringsRepositoryInterface $htmlStringsRepository
	) {
		$this->htmlStringsRepository = $htmlStringsRepository;
	}

	protected function onFilter( ...$args ) {
		$html = $args[0];
		return $this->htmlStringsRepository->getAllStringsFromHtml( $html );
	}
}
