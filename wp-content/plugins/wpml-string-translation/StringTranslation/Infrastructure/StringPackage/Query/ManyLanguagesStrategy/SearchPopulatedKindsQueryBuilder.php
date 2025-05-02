<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query\ManyLanguagesStrategy;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;
use WPML\StringTranslation\Application\StringPackage\Query\Criteria\SearchPopulatedKindsCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\SearchPopulatedKindsQueryBuilderInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\QueryBuilderTrait;

class SearchPopulatedKindsQueryBuilder implements SearchPopulatedKindsQueryBuilderInterface {
	use QueryBuilderTrait;
	use TranslationStatusQueryBuilderTrait;

	/** @var \wpdb */
	private $wpdb;

	/** @var \SitePress */
	private $sitepress;

	/** @var SettingsRepository */
	private $settingsRepository;

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
			LEFT JOIN {$this->wpdb->prefix}icl_translations target_t
			  ON target_t.trid = source_t.trid
			  AND target_t.language_code IN (" . wpml_prepare_in( $languageCodes ) . ")
			LEFT JOIN {$this->wpdb->prefix}icl_translation_status target_ts
			  ON target_ts.translation_id = target_t.translation_id
			WHERE
				sp.kind_slug = %s
				{$this->buildTranslationStatusConditionWrapper( $criteria, $languageCodes )}
			LIMIT 0,1;
        ";

		return $this->wpdb->prepare( $sql, $sourceLanguage, $stringPackageId );
	}

	private function buildTranslationStatusConditionWrapper(
		SearchPopulatedKindsCriteria $criteria,
		array $targetLanguageCodes
	) : string {
		return 'AND ' . $this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes );
	}
}
