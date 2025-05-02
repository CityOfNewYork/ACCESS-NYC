<?php

namespace WPML\StringTranslation\Application\StringHtml\Repository;

interface HtmlStringsRepositoryInterface {
	/**
	 * @return string[]
	 */
	public function getAllStringsFromHtml( string $html ): array;
}