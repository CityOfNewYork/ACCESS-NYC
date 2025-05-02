<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\FetchFiltersCriteria;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;

class FindDomainsAndPrioritiesQueryBuilder extends QueryBuilder {

	/** @var SettingsRepository */
	protected $settingsRepository;

	public function __construct(
		SettingsRepository $settingsRepository
	) {
		$this->settingsRepository = $settingsRepository;
	}

	private function getSelectColumnsForQuery(): string {
		return "
			DISTINCT strings.context as domain, strings.translation_priority
		";
	}

	public function build( FetchFiltersCriteria $criteria ) {
		$stringPositionsSql = '';
		if ( $this->shouldSelectOnlyAutoregistered( $criteria ) ) {
			$stringPositionsSql = $this->getStringPositionsSql($criteria);
		}

		$sql = "
            SELECT {$this->getSelectColumnsForQuery()}
            FROM {$this->getPrefix()}icl_strings strings
            
            {$this->getStringTranslationsSql( $criteria )}
            {$stringPositionsSql}
            
            {$this->buildWhereSql( $criteria )}
		";

		return $sql;
	}
}