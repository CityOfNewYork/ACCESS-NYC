<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query\MultiJoinStrategy;

use WPML\StringTranslation\Application\StringPackage\Query\SearchPopulatedKindsQueryBuilderInterface;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryBuilderInterface;

class QueryBuilderFactory {

	/** @var FindStringPackagesQueryBuilder */
	private $findStringPackagesQueryBuilder;

	/** @var SearchPopulatedKindsQueryBuilder */
	private $searchPopulatedKindsQueryBuilder;

	public function __construct(
		FindStringPackagesQueryBuilder $findStringPackagesQueryBuilder,
		SearchPopulatedKindsQueryBuilder $searchPopulatedKindsQueryBuilder
	) {
		$this->findStringPackagesQueryBuilder = $findStringPackagesQueryBuilder;
		$this->searchPopulatedKindsQueryBuilder = $searchPopulatedKindsQueryBuilder;
	}

	public function createFindStringPackagesQueryBuilder(): FindStringPackagesQueryBuilderInterface {
		return $this->findStringPackagesQueryBuilder;
	}

	public function createSearchPopulatedKindsQueryBuilder(): SearchPopulatedKindsQueryBuilderInterface {
		return $this->searchPopulatedKindsQueryBuilder;
	}
}