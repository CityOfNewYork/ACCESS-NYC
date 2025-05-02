<?php

namespace WPML\StringTranslation\Application\StringPackage\Query;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\SearchPopulatedKindsCriteria;

interface SearchPopulatedKindsQueryBuilderInterface {

	public function build( SearchPopulatedKindsCriteria $criteria, $stringPackageId ): string;
}