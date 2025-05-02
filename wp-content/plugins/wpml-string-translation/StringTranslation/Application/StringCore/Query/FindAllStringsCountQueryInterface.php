<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;

interface FindAllStringsCountQueryInterface {

	public function execute( SearchCriteria $criteria ): int;
}