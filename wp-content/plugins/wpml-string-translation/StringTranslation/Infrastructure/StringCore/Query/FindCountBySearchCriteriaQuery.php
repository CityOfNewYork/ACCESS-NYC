<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindCountBySearchCriteriaQueryInterface;

class FindCountBySearchCriteriaQuery implements FindCountBySearchCriteriaQueryInterface {

	/** @var FindCountBySearchCriteriaQueryBuilder */
	private $queryBuilder;

	/**
	 * @param FindCountBySearchCriteriaQueryBuilder $queryBuilder
	 */
	public function __construct(
		FindCountBySearchCriteriaQueryBuilder $queryBuilder
	) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @param SearchCriteria $criteria
	 */
	public function execute( SearchCriteria $criteria ): int {
		global $wpdb;

		$query = $this->queryBuilder->build( $criteria );
		$res = $wpdb->get_var( $query );

		return $res;
	}
}
