<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;

class FindAllStringsCountQueryBuilder extends QueryBuilder {

	/** @var SettingsRepository */
	protected $settingsRepository;

	public function __construct(
		SettingsRepository $settingsRepository
	) {
		$this->settingsRepository = $settingsRepository;
	}

	/**
	 * @codingStandardsIgnoreStart
	 *
	 * @param SearchCriteria $criteria
	 *
	 * @return string
	 */
	public function build( SearchCriteria $criteria ) {
		$sql = "
            SELECT COUNT(*)
            FROM {$this->getPrefix()}icl_strings strings
            
            {$this->getStringTranslationsSql( $criteria )}
            {$this->buildWhereSql( $criteria )}
            
            ORDER BY id DESC
        ";

		return $sql;
	}
}
