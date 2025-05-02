<?php

namespace WPML\StringTranslation\Application\StringPackage\Query;


use WPML\StringTranslation\Application\StringPackage\Query\Criteria\SearchPopulatedKindsCriteria;

interface SearchPopulatedKindsQueryInterface {


	/**
	 * Will get all the PostType id's that matches the Criteria.
	 *
	 * @param SearchPopulatedKindsCriteria $criteria
	 *
	 * @return array<string>
	 */
	public function get( SearchPopulatedKindsCriteria $criteria );

}
