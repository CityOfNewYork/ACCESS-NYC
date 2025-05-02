<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\FetchFiltersCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Dto\FiltersDto;

interface FetchFiltersQueryInterface {

	/**
	 * @return FiltersDto
	 */
	public function execute( FetchFiltersCriteria $criteria );
}