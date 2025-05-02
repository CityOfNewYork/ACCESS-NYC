<?php

namespace WPML\StringTranslation\Application\StringHtml\Repository;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface GettextStringsRepositoryInterface {
	/**
	 * @param StringItem[] $gettextStrings
	 * @param string[]     $htmlStrings
	 *
	 * @return StringItem[]
	 */
	public function filterOnlyGettextStringsThatMatchesHtmlStrings( array $gettextStrings, array $htmlStrings ): array;
}