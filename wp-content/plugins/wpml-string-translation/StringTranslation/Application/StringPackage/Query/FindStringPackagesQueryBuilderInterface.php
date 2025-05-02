<?php

namespace WPML\StringTranslation\Application\StringPackage\Query;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;

interface FindStringPackagesQueryBuilderInterface {

	public function build( StringPackageCriteria $criteria ): string;
}