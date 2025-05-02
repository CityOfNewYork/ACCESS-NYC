<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query\MultiJoinStrategy;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;
use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\FindStringPackagesQueryBuilderInterface;
use WPML\StringTranslation\Infrastructure\StringPackage\Query\QueryBuilderTrait;

class FindStringPackagesQueryBuilder implements FindStringPackagesQueryBuilderInterface {
	use QueryBuilderTrait;
	use TranslationStatusQueryBuilderTrait;

	const SP_COLUMNS = '
        sp.ID,
        sp.name,
        sp.kind_slug,
        sp.title,
        sp.word_count,
        sp.translator_note
    ';

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
		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
		$this->settingsRepository = $settingsRepository;
	}

	public function build( StringPackageCriteria $criteria ): string {
		$sourceLanguage = $this->getSourceLanguageCode( $criteria );
		$targetLanguageCodes = $this->getTargetLanguageCodes( $criteria );
		$fields = $this->getFields();
		$stringPackageId = $criteria->getType();

		$sql = "
      SELECT
          {$fields}
      FROM {$this->wpdb->prefix}icl_string_packages sp
      INNER JOIN {$this->wpdb->prefix}icl_translations source_t
          ON source_t.element_id = sp.ID
          AND source_t.element_type = CONCAT('package_', sp.kind_slug)
          AND source_t.language_code = %s
      {$this->buildTargetLanguageJoins( $targetLanguageCodes )}
      WHERE sp.kind_slug = %s
          {$this->buildPostTitleCondition( $criteria )}
          {$this->buildTranslationStatusConditionWrapper( $criteria, $targetLanguageCodes )}
      GROUP BY sp.ID
      {$this->buildSortingQueryPart( $criteria )}
      {$this->buildPagination( $criteria )}
    ";

		return $this->wpdb->prepare( $sql, $sourceLanguage, $stringPackageId );
	}

	private function getFields(): string {
		$spColumns = self::SP_COLUMNS;

		return "
            {$spColumns}
		";
	}

	private function buildTranslationStatusConditionWrapper(
		StringPackageCriteria $criteria,
		array $targetLanguageCodes
	) : string {
		return 'AND ' . $this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes );
	}
}