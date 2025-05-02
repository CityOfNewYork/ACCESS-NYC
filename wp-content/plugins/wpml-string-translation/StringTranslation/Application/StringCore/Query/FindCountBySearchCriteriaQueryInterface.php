<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;

interface FindCountBySearchCriteriaQueryInterface {

	public function execute( SearchCriteria $criteria ): int;
}