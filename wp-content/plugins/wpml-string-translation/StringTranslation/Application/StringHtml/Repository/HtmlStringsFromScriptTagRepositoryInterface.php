<?php

namespace WPML\StringTranslation\Application\StringHtml\Repository;

interface HtmlStringsFromScriptTagRepositoryInterface {
	public function hasCustomPlaceholdersFromAnyJsTemplateEngine( string $value ): bool;
	public function replaceCustomPlaceholdersFromAnyJsTemplateEngineWithHtmlComments( string $value ): string;
	public function maybeFixBrokenTags( string $html ): string;
}