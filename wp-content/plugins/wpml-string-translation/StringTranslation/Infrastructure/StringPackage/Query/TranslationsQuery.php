<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface as SettingsRepository;

class TranslationsQuery {
	use QueryBuilderTrait;

	/** @var \wpdb */
	private $wpdb;

	/** @var SettingsRepository */
	private $settingsRepository;

	public function __construct(
		$wpdb,
		SettingsRepository $settingsRepository
	) {
		$this->wpdb = $wpdb;
		$this->settingsRepository = $settingsRepository;
	}

	public function get( array $stringPackages, StringPackageCriteria $criteria ): array {
		if ( empty( $stringPackages ) ) {
			return [];
		}

		$sourceLanguage = $this->getSourceLanguageCode( $criteria );
		$targetLanguageCodes = $this->getTargetLanguageCodes( $criteria );

		$stringPackageIds = [];
		$stringPackageKindSlugs = [];
		foreach ( $stringPackages as $stringPackage ) {
			$stringPackageIds[] = $stringPackage['ID'];
			$stringPackageKindSlugs[] = 'package_' . $stringPackage['kind_slug'];
		}

		$stringPackageKindSlugs = array_values( array_unique( $stringPackageKindSlugs ) );

		$sql = "
      SELECT 
        source_t.element_id as string_package_id,
        target_t.language_code,
        CASE
          WHEN ts.needs_update = 1 THEN 3
          WHEN target_t.trid IS NOT NULL
            AND (
              target_t.source_language_code IS NULL
              OR ts.status = 0
            ) THEN 10
          ELSE IFNULL(ts.status, 0)
        END as status,
        ts.needs_update,
        ts.review_status,
        target_t.element_id as translation_element_id,
        ts.rid
      FROM {$this->wpdb->prefix}icl_translations source_t
      INNER JOIN {$this->wpdb->prefix}icl_translations target_t
        ON target_t.trid = source_t.trid
        AND target_t.language_code IN (" . wpml_prepare_in( $targetLanguageCodes, '%s' ) . ")
      LEFT JOIN {$this->wpdb->prefix}icl_translation_status ts
        ON ts.translation_id = target_t.translation_id
      WHERE source_t.element_id IN (" . wpml_prepare_in( $stringPackageIds, '%d' ) . ")
        AND source_t.element_type IN (" . wpml_prepare_in( $stringPackageKindSlugs, '%s' ) . ")
        AND source_t.language_code = %s
        AND target_t.source_language_code = %s
    ";

		$sql = $this->wpdb->prepare( $sql, $sourceLanguage, $sourceLanguage );

		$translationStatuses = $this->wpdb->get_results( $sql, ARRAY_A );

		return $this->addTranslationStatusString( $stringPackages, $translationStatuses, $criteria );
	}

	private function addTranslationStatusString( array $packages, array $translationStatuses, StringPackageCriteria $criteria ): array {
		$languageCodes = $this->getTargetLanguageCodes( $criteria );

		foreach ( $packages as &$package ) {
			$statusByLanguage = [];
			foreach ( $languageCodes as $languageCode ) {
				$statusByLanguage[ $languageCode ] = [
					'languageCode' => $languageCode,
					'status' => 0,
					'reviewStatus' => '',
					'rid' => '',
				];
			}

			foreach ( $translationStatuses as $translationStatus ) {
				if ((int) $package['ID'] === (int) $translationStatus['string_package_id'] ) {
					$statusByLanguage[ $translationStatus['language_code'] ] = [
						'languageCode' => $translationStatus['language_code'],
						'status' => $translationStatus['status'],
						'reviewStatus' => $translationStatus['review_status'],
						'rid' => $translationStatus['rid'],
					];
				}
			}

			$statusByLanguageStr = [];
			foreach ( $statusByLanguage as $language => $data ) {
				$languageItems = [];
				foreach ( $data as $key => $value ) {
					$languageItems[] = $key . ':' . $value;
				}
				$statusByLanguageStr[] = implode( ',', $languageItems );
			}

			$package['translation_statuses'] = implode( ';', $statusByLanguageStr );
		}

		return $packages;
	}
}