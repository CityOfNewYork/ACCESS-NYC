<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsCountQueryInterface;

class FindAllStringsCountQuery implements FindAllStringsCountQueryInterface {

	/** @var FindAllStringsCountQueryBuilder */
	private $queryBuilder;

	/**
	 * @param FindAllStringsCountQueryBuilder $queryBuilder
	 */
	public function __construct(
		FindAllStringsCountQueryBuilder $queryBuilder
	) {
		$this->queryBuilder = $queryBuilder;
	}

	public function execute( SearchCriteria $criteria ): int {
		global $wpdb;

		$query = $this->queryBuilder->build( $criteria );
		$count = $wpdb->get_var( $query );

		return $count;
	}
}
