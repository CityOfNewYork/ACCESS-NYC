<?php
namespace WPML\StringTranslation\Infrastructure\StringPackage\Query;

use WPML\StringTranslation\Application\StringPackage\Query\SearchPopulatedKindsQueryBuilderInterface;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryBuilderInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\ManyLanguagesStrategy\QueryBuilderFactory as ManyLanguagesFactory;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\MultiJoinStrategy\QueryBuilderFactory as MultiJoinFactory;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class QueryBuilderResolver {

	const MAX_LANGUAGES_FOR_MULTI_JOIN_STRATEGY = 29;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var ManyLanguagesFactory */
	private $manyLanguagesFactory;

	/** @var MultiJoinFactory */
	private $multiJoinFactory;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository,
		ManyLanguagesFactory $manyLanguagesFactory,
		MultiJoinFactory $multiJoinFactory
	) {
		$this->settingsRepository   = $settingsRepository;
		$this->manyLanguagesFactory = $manyLanguagesFactory;
		$this->multiJoinFactory     = $multiJoinFactory;
	}

	public function resolveFindStringPackagesQueryBuilder(): FindStringPackagesQueryBuilderInterface {
		if ( $this->isLimitReached() ) {
			return $this->manyLanguagesFactory->createFindStringPackagesQueryBuilder();
		}

		return $this->multiJoinFactory->createFindStringPackagesQueryBuilder();
	}

	public function resolveSearchPopulatedKindsQueryBuilder(): SearchPopulatedKindsQueryBuilderInterface {
		if ( $this->isLimitReached() ) {
			return $this->manyLanguagesFactory->createSearchPopulatedKindsQueryBuilder();
		}

		return $this->multiJoinFactory->createSearchPopulatedKindsQueryBuilder();
	}

	private function isLimitReached(): bool {
		return count( $this->settingsRepository->getActiveSecondaryLanguageCodes() ) >= self::MAX_LANGUAGES_FOR_MULTI_JOIN_STRATEGY;
	}
}