<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Dto\StringDto;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;

interface FindAllStringsQueryInterface {

	/**
	 * @return StringDto[]
	 */
	public function execute( SearchCriteria $criteria, SearchSelectCriteria $selectCriteria ): array;
}