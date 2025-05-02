<?php

namespace WPML\StringTranslation\Application\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\DomainValueAndContextCriteria;

interface FindByDomainValueAndContextQueryInterface {

	public function execute(DomainValueAndContextCriteria $criteria );
}