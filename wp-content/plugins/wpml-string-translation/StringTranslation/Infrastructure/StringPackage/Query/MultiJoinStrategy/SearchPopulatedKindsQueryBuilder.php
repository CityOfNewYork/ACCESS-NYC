<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query\MultiJoinStrategy;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;
use WPML\StringTranslation\Application\StringPackage\Query\Criteria\SearchPopulatedKindsCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\SearchPopulatedKindsQueryBuilderInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\QueryBuilderTrait;

class SearchPopulatedKindsQueryBuilder implements SearchPopulatedKindsQueryBuilderInterface {
	use QueryBuilderTrait;
	use TranslationStatusQueryBuilderTrait;

	/** @var \SitePress */
	private $sitepress;

	/** @var SettingsRepository */
	private $settingsRepository;

	/** @var \wpdb */
	private $wpdb;

	public function __construct(
		$wpdb,
		$sitepress,
		SettingsRepository $settingsRepository
	) {
		$this->wpdb               = $wpdb;
		$this->sitepress          = $sitepress;
		$this->settingsRepository = $settingsRepository;
	}

	public function build( SearchPopulatedKindsCriteria $criteria, $stringPackageId ): string {
		$sourceLanguage = $this->getSourceLanguageCode( $criteria );
		$languageCodes = $this->getTargetLanguageCodes( $criteria );

		$sql = "
			SELECT sp.kind_slug
			FROM {$this->wpdb->prefix}icl_string_packages sp
			INNER JOIN {$this->wpdb->prefix}icl_translations source_t
			  ON source_t.element_id = sp.ID
			  AND source_t.element_type = CONCAT('package_', sp.kind_slug)
			  AND source_t.language_code = %s
			{$this->buildTargetLanguageJoins( $languageCodes )}
			WHERE
				{$this->buildTranslationStatusCondition( $criteria, $languageCodes )}
				AND sp.kind_slug = %s
			LIMIT 0,1;
        ";

		return $this->wpdb->prepare( $sql, $sourceLanguage, $stringPackageId );
	}
}
