<?php

namespace WPML\StringTranslation\Application\Translation\Query;

use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationDetailsDto;

interface FindTranslationDetailsQueryInterface {

	/**
	 * @param int[]    $stringIds
	 * @param string[] $languageCodes
	 *
	 * @return TranslationDetailsDto[]
	 */
	public function execute( array $stringIds, array $languageCodes ): array;
}