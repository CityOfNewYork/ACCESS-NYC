<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;

class FindBySearchCriteriaQueryBuilder extends QueryBuilder {

	/** @var SettingsRepository */
	protected $settingsRepository;

	public function __construct(
		SettingsRepository $settingsRepository
	) {
		$this->settingsRepository = $settingsRepository;
	}

	private function buildSortingQueryPart( SearchCriteria $criteria ): string {
		$allowedSortingDirections = [ 'DESC', 'ASC' ];
		$direction                = $allowedSortingDirections[0];

		$queryPart = 'ORDER BY MAX(strings.string_type) DESC, strings.id';

		if ( $criteria->getSorting() ) {
			$sortingCriteria = $criteria->getSorting();
			$sortingOrder    = strtoupper( $sortingCriteria['order'] );

			$direction = in_array( $sortingOrder, $allowedSortingDirections ) ?
				$sortingOrder :
				$direction;

			if ( $sortingCriteria['by'] === 'title' ) {
				$queryPart = 'ORDER BY MAX(strings.string_type) DESC, MAX(strings.value)';
			}
		}

		return $queryPart . ' ' . $direction;
	}


	/**
	 * @codingStandardsIgnoreStart
	 *
	 * @param SearchCriteria $criteria
	 * @param SearchSelectCriteria $selectCriteria
	 *
	 * @return string
	 */
	public function buildStringsQuery( SearchCriteria $criteria, SearchSelectCriteria $selectCriteria ) {
		$sql = "
            SELECT {$this->getSelectColumns( $selectCriteria )}
            FROM {$this->getPrefix()}icl_strings strings
            
            {$this->getStringTranslationsSql( $criteria )}
            {$this->getStringPositionsSql( $criteria )}
            
            {$this->buildWhereSql( $criteria )}
            GROUP BY
                {$this->getGroupByColumns()}
            {$this->buildSortingQueryPart( $criteria )}
            {$this->buildPagination( $criteria )}
        ";

		return $sql;
	}

	/**
	 * @param int[]    $stringIds
	 * @param string[] $targetLanguageCodes
	 *
	 * @return string
	 */
	public function buildStringTranslationsQuery(
		array $stringIds,
		array $targetLanguageCodes
	) {
		$sql = "
            SELECT st.status, st.language, st.string_id, st.value
            FROM {$this->getPrefix()}icl_string_translations st
            WHERE st.string_id IN (" . wpml_prepare_in( $stringIds, '%d' ) . ")
            AND st.language IN (" . wpml_prepare_in( $targetLanguageCodes, '%s' ) . ")
        ";

		return $sql;
	}
}
