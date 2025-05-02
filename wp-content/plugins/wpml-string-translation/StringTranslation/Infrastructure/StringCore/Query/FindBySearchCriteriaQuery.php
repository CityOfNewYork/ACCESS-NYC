<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Dto\StringWithTranslationStatusDto;
use WPML\StringTranslation\Application\StringCore\Query\FindBySearchCriteriaQueryInterface;
use WPML\StringTranslation\Application\Translation\Query\Dto\TranslationDetailsDto;
use WPML\StringTranslation\Infrastructure\Translation\TranslationStatusesParser;
use WPML\StringTranslation\Application\Translation\Query\FindTranslationDetailsQueryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class FindBySearchCriteriaQuery implements FindBySearchCriteriaQueryInterface {

	/** @var FindBySearchCriteriaQueryBuilder */
	private $queryBuilder;

	/** @var TranslationStatusesParser */
	private $translationStatusesParser;

	/** @var FindTranslationDetailsQueryInterface */
	private $findTranslationDetailsQuery;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/**
	 * @param FindBySearchCriteriaQueryBuilder $queryBuilder
	 * @param TranslationStatusesParser $translationStatusesParser
	 * @param FindTranslationDetailsQueryInterface $findTranslationDetailsQuery
	 * @param SettingsRepositoryInterface $settingsRepository
	 */
	public function __construct(
		FindBySearchCriteriaQueryBuilder     $queryBuilder,
		TranslationStatusesParser            $translationStatusesParser,
		FindTranslationDetailsQueryInterface $findTranslationDetailsQuery,
		SettingsRepositoryInterface          $settingsRepository
	) {
		$this->queryBuilder                = $queryBuilder;
		$this->translationStatusesParser   = $translationStatusesParser;
		$this->findTranslationDetailsQuery = $findTranslationDetailsQuery;
		$this->settingsRepository          = $settingsRepository;
	}

	/**
	 * @param SearchCriteria $criteria
	 */
	public function execute( SearchCriteria $criteria ) {
		global $wpdb;

		$query = $this->queryBuilder->buildStringsQuery( $criteria, new SearchSelectCriteria( [
			'id',
			'language',
			'context',
			'gettext_context',
			'name',
			'value',
			'status',
			'translation_priority',
			'string_type',
			'component_id',
			'component_type',
			'sources',
			'word_count',
		] ) );
		$strings = $wpdb->get_results( $query, ARRAY_A );
		foreach ( $strings as &$string ) {
			$string['string_id'] = (int)$string['string_id'];
			$string['status']    = (int)$string['status'];
		}

		if ( count( $strings ) === 0 ) {
			return [];
		}

		$allTargetLanguageCodes = $this->settingsRepository->getAllTargetLanguagesBySource( $criteria->getSourceLanguageCode() );

		$criteria->addIds( $strings );
		$translationDetails = $this->findTranslationDetailsQuery->execute(
			$criteria->getIds(),
			$allTargetLanguageCodes
		);
		usort($translationDetails, function( $a, $b ) {
			return $a->getLanguageCode() <=> $b->getLanguageCode();
		});

		$query = $this->queryBuilder->buildStringTranslationsQuery(
			$criteria->getIds(),
			$allTargetLanguageCodes
		);
		$stringsTranslations = $wpdb->get_results( $query, ARRAY_A );
		foreach ( $stringsTranslations as &$stringTranslation ) {
			$stringTranslation['string_id'] = (int)$stringTranslation['string_id'];
			$stringTranslation['status']    = (int)$stringTranslation['status'];
		}
		unset( $stringTranslation ); // Clear the reference.
		usort($stringsTranslations, function( $a, $b ) {
			return $a['language'] <=> $b['language'];
		});

		foreach ( $strings as &$string ) {
			if ( ! array_key_exists( 'translations', $string ) ) {
				$string['translations'] = [];
			}

			foreach ( $stringsTranslations as $stringTranslation ) {
				if ( $stringTranslation['string_id'] !== $string['string_id'] ) {
					continue;
				}

				$string['translations'][] = $stringTranslation;
			}

			$string['translationDetails'] = array_values(
				array_filter(
					$translationDetails,
					function( $item ) use ( $string ) {
						return $string['string_id'] === $item->getStringId();
					}
				)
			);
		}

		foreach ( $strings as &$string ) {
			$allTranslations = [];
			foreach ( $allTargetLanguageCodes as $targetLanguageCode ) {
				$allTranslations[ $targetLanguageCode ] = null;
			}

			foreach ( $string['translations'] as $translation ) {
				$allTranslations[ $translation['language'] ] = $translation;
			}

			foreach ( $allTranslations as $targetLanguageCode => $translation ) {
				if ( ! is_null( $translation ) ) {
					continue;
				}

				$status = ICL_TM_NOT_TRANSLATED;
				if ( $targetLanguageCode === $string['string_language'] ) {
					$status = ICL_TM_COMPLETE;
				}

				$allTranslations[ $targetLanguageCode ] = [
					'status' => $status,
					'language' => $targetLanguageCode,
					'string_id' => $string['string_id'],
					'value' => '',
				];
			}

			$string['translations'] = array_values( $allTranslations );
		}

		$strings = $this->mapStringsCollection( $strings );

		return $strings;
	}

	private function mapStringsCollection( array $strings ): array {
		return array_map(
			function( $string ) {
				return $this->mapString( $string );
			},
			$strings
		);
	}

	private function mapString( array $string ): StringWithTranslationStatusDto {
		$translationsByStringId = [];
		foreach ( $string['translations'] as $translation ) {
			$translationsByStringId[ $translation['string_id'] . $translation['language'] ] = $translation;
		}

		$details     = [];
		$indexedJobs = [];
		/** @var TranslationDetailsDto $translationDetails */
		foreach ( $string['translationDetails'] as $translationDetails ) {
			$key          = $translationDetails->getStringId() . $translationDetails->getLanguageCode();
			$code         = $translationDetails->getLanguageCode();
			$rid          = $translationDetails->getRid();
			$status       = $translationsByStringId[ $key ]['status'];
			$automatic    = $translationDetails->getAutomatic();
			$reviewStatus = $translationDetails->getReviewStatus();
			$tranlatorId  = $translationDetails->getTranslatorId();

			$detailsStr = 'languageCode:' . $code . ',rid:' . $rid . ',status:' . $status . ',automatic:' . $automatic;
			if ( is_string( $reviewStatus ) && strlen( $reviewStatus ) > 0 ) {
				$detailsStr .= ',reviewStatus=' . $reviewStatus;
			}
			if ( is_numeric( $tranlatorId ) && $tranlatorId > 0 ) {
				$detailsStr .= ',translatorId=' . $tranlatorId;
			}

			$details[]   = $detailsStr;
			$translation = null;
			if ( isset( $translationsByStringId[ $key ] ) ) {
				$translation = $translationsByStringId[ $key ];
			}

			$indexedJobs[ $translationDetails->getRid() ] = [
				'job_id' => $translationDetails->getJobId(),
				'automatic' => $translationDetails->getAutomatic(),
				'translation_service' => $translationDetails->getTranslationService(),
				'editor' => $translationDetails->getEditor(),
				'review_status' => $translationDetails->getReviewStatus(),
				'translated' => is_array( $translation ) ? $translation['value'] : '',
				'translator_id' => $translationDetails->getTranslatorId(),
			];
		}

		foreach ( $string['translations'] as $translation ) {
			$hasTranslationDetailsForLanguage = false;
			foreach ( $string['translationDetails'] as $translationDetails ) {
				if ( $translationDetails->getLanguageCode() === $translation['language'] ) {
					$hasTranslationDetailsForLanguage = true;
					break;
				}
			}

			if ( $hasTranslationDetailsForLanguage ) {
				continue;
			}

			$details[]   = 'languageCode:' . $translation['language'] . ',status:' . $translation['status'];
		}

		$translationStatuses = implode( ';', $details );

		$translationStatuses = $this->translationStatusesParser->parse( $translationStatuses, $indexedJobs );

		$sources = array_values(
			array_unique(
				array_map(
					function( $source ) {
						return (int)$source;
					},
					is_string( $string['sources'] ) ? explode( ',', $string['sources'] ) : []
				)
			)
		);

		return new StringWithTranslationStatusDto(
			$string['string_id'],
			$string['string_language'],
			html_entity_decode( $string['domain'], ENT_QUOTES ),
			$string['context'],
			$string['name'],
			html_entity_decode( $string['value'], ENT_QUOTES ),
			(int) $string['status'],
			$string['translation_priority'],
			is_numeric( $string['word_count'] ) ? (int) $string['word_count'] : 0,
			(int) $string['string_type'],
			(int) $string['component_type'],
			$sources,
			$translationStatuses
		);
	}
}
