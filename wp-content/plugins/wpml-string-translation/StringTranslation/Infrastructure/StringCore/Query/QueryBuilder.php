<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\FetchFiltersCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;
use WPML\StringTranslation\Infrastructure\Translation\TranslationStatusesParser;

abstract class QueryBuilder {

	protected function getPrefix(): string {
		global $wpdb;
		return $wpdb->prefix;
	}

	protected function prepare( $sql, ...$args ): string {
		global $wpdb;
		return $wpdb->prepare( $sql, $args );
	}

	protected function prepareLike( $value ): string {
		global $wpdb;
		return $wpdb->esc_like( $value );
	}

	protected function buildPagination( SearchCriteria $criteria ): string {
		return $this->prepare( ' LIMIT %d OFFSET %d', $criteria->getLimit(), $criteria->getOffset() );
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function buildWhereSql( $criteria ): string {
		$sqlParts = $this->getWhereSqlParts( $criteria );

		if ( count( $sqlParts ) === 0 ) {
			return '';
		}

		return ' WHERE ' . implode( ' AND ', $sqlParts );
	}

	protected function getSelectColumns( SearchSelectCriteria $dto ): string {
		$sql = [];

		if ( $dto->shouldSelect( 'id' ) ) {
			$sql[] = 'strings.id AS string_id';
		}
		if ( $dto->shouldSelect( 'language' ) ) {
			$sql[] = 'strings.language AS string_language';
		}
		if ( $dto->shouldSelect( 'context' ) ) {
			$sql[] = 'strings.context as domain';
		}
		if ( $dto->shouldSelect( 'gettext_context' ) ) {
			$sql[] = 'strings.gettext_context as context';
		}
		if ( $dto->shouldSelect( 'name' ) ) {
			$sql[] = 'strings.name';
		}
		if ( $dto->shouldSelect( 'value' ) ) {
			$sql[] = 'strings.value';
		}
		if ( $dto->shouldSelect( 'status' ) ) {
			$sql[] = 'strings.status';
		}
		if ( $dto->shouldSelect( 'translation_priority' ) ) {
			$sql[] = 'strings.translation_priority';
		}
		if ( $dto->shouldSelect( 'string_type' ) ) {
			$sql[] = 'strings.string_type';
		}
		if ( $dto->shouldSelect( 'component_id' ) ) {
			$sql[] = 'strings.component_id';
		}
		if ( $dto->shouldSelect( 'component_type' ) ) {
			$sql[] = 'strings.component_type';
		}
		if ( $dto->shouldSelect( 'sources' ) ) {
			$sql[] = 'string_positions.sources';
		}
		if ( $dto->shouldSelect( 'word_count' ) ) {
			$sql[] = 'word_count';
		}

		return implode( ',', $sql );
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function shouldSelectOnlyAutoregistered( $criteria ) {
		$hasSource = in_array(
			$criteria->getSource(),
			[
				ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND,
				ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_BACKEND,
			],
			true
		);

		return $hasSource && ! $this->shouldSelectOnlyNotAutoregistered( $criteria );
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function shouldSelectOnlyNotAutoregistered( $criteria ): bool {
		$kind                     = $criteria->getKind();
		$hasNotAutoregisteredKind = is_int( $kind ) && $kind === StringItem::STRING_TYPE_DEFAULT;

		return $hasNotAutoregisteredKind;
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function shouldCheckForInProgressStatusInStringTranslations( $criteria ): bool {
		$statuses = $criteria->getTranslationStatuses();

		return (
			in_array( ICL_TM_WAITING_FOR_TRANSLATOR, $statuses ) &&
			in_array( ICL_TM_IN_PROGRESS, $statuses )
		);
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 *
	 * icl_strings.status(ICL_STRING_TRANSLATION_PARTIAL=2) matches ICL_TM_IN_PROGRESS=2, we should filter it out if we
	 * need to check for in progress strings only.
	 *
	 * @return int[]
	 */
	protected function filterOutTranslationPartialStatusFromStrings( $criteria ): array {
		return array_filter(
			$criteria->getTranslationStatuses(),
			function( $status ) {
				return $status !== ICL_TM_IN_PROGRESS;
			}
		);
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function getStringTranslationsSql( $criteria ): string {
		if ( ! $this->shouldCheckForInProgressStatusInStringTranslations( $criteria ) ) {
			return '';
		}

		return "
            LEFT JOIN {$this->getPrefix()}icl_string_translations string_translations
                ON strings.id = string_translations.string_id
		";
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function getStringPositionsSql( $criteria ): string {
		return "
            LEFT JOIN (
                SELECT string_id,
                GROUP_CONCAT(kind SEPARATOR ', ') AS sources
                FROM {$this->getPrefix()}icl_string_positions
                GROUP BY string_id
            ) AS string_positions
            ON strings.id = string_positions.string_id
		";
	}

	protected function getGroupByColumns(): string {
		return '
            strings.id
        ';
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function buildHavingSql( $criteria ): string {
		$sqlParts = [];
		foreach ( $criteria->getTranslationStatuses() as $status ) {
			$sqlParts[] = "translation_statuses LIKE '%status:" . $this->prepareLike( $status ) . ",%'";
		}

		if ( count( $sqlParts ) === 0 ) {
			return '';
		}

		return ' HAVING (' . implode( ' OR ', $sqlParts ) . ')';
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function buildLanguagesCrossJoin( $criteria ): string {
		$buildLanguageSelect = function ( string $code ): string {
			return $this->prepare(
				'SELECT %s AS language_code',
				$code
			);
		};

		$codes = $this->settingsRepository->getAllTargetLanguagesBySource( $criteria->getSourceLanguageCode() );

		$languages = implode(
			' UNION ALL ',
			array_map(
				function ( $languageCode ) use ( $buildLanguageSelect ) {
					return $buildLanguageSelect( $languageCode );
				},
				$codes
			)
		);

		return "
            CROSS JOIN (
                {$languages}
            ) AS langs
        ";
	}

	/**
	 * @param $source int|null
	 */
	private function getSourcesSql( $source ): array {
		if ( $source === ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND ) {
			return [ $source ];
		}

		return [
			ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_BACKEND,
			ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_AJAX,
			ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_REST,
		];
	}

	/**
	 * @param SearchCriteria|FetchFiltersCriteria $criteria
	 */
	protected function getWhereSqlParts( $criteria ): array {
		$selectOnlyAutoregistered    = $this->shouldSelectOnlyAutoregistered( $criteria );
		$selectOnlyNotAutoregistered = $this->shouldSelectOnlyNotAutoregistered( $criteria );

		$sqlParts   = [];
		$sqlParts[] = 'strings.string_package_id IS NULL';
		$sqlParts[] = 'strings.value != ""';

		if ( $selectOnlyAutoregistered || $selectOnlyNotAutoregistered ) {
			$kind       = $selectOnlyAutoregistered ? StringItem::STRING_TYPE_AUTOREGISTER : StringItem::STRING_TYPE_DEFAULT;
			$sqlParts[] = $this->prepare( 'strings.string_type = %d', $kind );
		}

		if ( $criteria->getType() && $selectOnlyAutoregistered ) {
			$sqlParts[] = $this->prepare( 'strings.component_type = %d', $criteria->getType() );
		}

		if ( $criteria->getSource() && $selectOnlyAutoregistered ) {
			$like = '%' . $this->prepareLike( ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND ) . '%';
			if ( $criteria->getSource() === ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_FRONTEND ) {
				$sqlParts[] = '(' . $this->prepare( 'string_positions.sources IS NOT NULL AND string_positions.sources LIKE %s', $like ) . ')';
			} else {
				$sqlParts[] = '(' . $this->prepare( 'string_positions.sources IS NULL OR string_positions.sources NOT LIKE %s', $like ) . ')';
			}
		}
		if ( $criteria->getDomain() ) {
			$domain = $criteria->getDomain();
			$escDomain = esc_html( $criteria->getDomain() );

			if ( $domain === $escDomain ) {
				$sqlParts[] = $this->prepare('strings.context = %s', $domain );
			} else {
				$sqlParts[] = $this->prepare('strings.context = %s OR strings.context = %s', $domain, $escDomain );
			}
		}
		if ( $criteria->getTitle() ) {
			$title = $this->prepareLike( $criteria->getTitle() );
			$escTitle = $this->prepareLike( esc_html( $criteria->getTitle() ) );

			if ( $title === $escTitle ) {
				$sqlParts[] = $this->prepare('strings.value LIKE %s', '%' . $title . '%');
			} else {
				$sqlParts[] = $this->prepare('strings.value LIKE %s OR strings.value LIKE %s', '%' . $title . '%', '%' . $escTitle . '%' );
			}
		}
		if ( $criteria->getTranslationPriority() ) {
			$sqlParts[] = $this->prepare( 'strings.translation_priority = %s', $criteria->getTranslationPriority() );
		}

		$defaultLanguageCode = $this->settingsRepository->getDefaultLanguageCode();
		$isDefaultLanguageEn = $defaultLanguageCode === 'en';
		$sourceLanguageCode = $criteria->getSourceLanguageCode();
		$langCodesToShow = $sourceLanguageCode ? [ $sourceLanguageCode ] : [];
		if ( ! $isDefaultLanguageEn && $sourceLanguageCode === $defaultLanguageCode ) {
			// Always add english strings when default language is selected.
			$langCodesToShow[] = 'en';
		}

		if ( $langCodesToShow ) {
			$sqlParts[] = 'strings.language IN (' . wpml_prepare_in( $langCodesToShow ) . ')';
		}

		// We should remove strings with translation partial status when we are on 'In Progress' tab, but not when we are on 'Not Completed' tab.
		$stringStatuses = $this->shouldCheckForInProgressStatusInStringTranslations( $criteria ) && count( $criteria->getTranslationStatuses() ) === 2
			? $this->filterOutTranslationPartialStatusFromStrings( $criteria )
			: $criteria->getTranslationStatuses();
		$stringTranslationStatuses    = $this->shouldCheckForInProgressStatusInStringTranslations( $criteria ) ? [ ICL_TM_IN_PROGRESS ] : [];
		$hasStringStatuses            = count( $stringStatuses ) > 0;
		$hasStringTranslationStatuses = count( $stringTranslationStatuses ) > 0;
		if ( $hasStringStatuses || $hasStringTranslationStatuses ) {
			$stringsSql            = 'strings.status IN (' . wpml_prepare_in( $stringStatuses ) . ')';
			$stringTranslationsSql = 'string_translations.status IN (' . wpml_prepare_in( $stringTranslationStatuses ) . ')';

			if ( $hasStringStatuses && $hasStringTranslationStatuses ) {
				$sqlParts[] = '(' . $stringsSql . ' OR ' . $stringTranslationsSql . ')';
			} else if ( $hasStringTranslationStatuses ) {
				$sqlParts[] = $stringTranslationsSql;
			} else {
				$sqlParts[] = $stringsSql;
			}
		}

		if ( $criteria instanceof SearchCriteria && count( $criteria->getIds() ) > 0 ) {
			$sqlParts[] = 'strings.id IN (' . wpml_prepare_in( $criteria->getIds() ) . ')';
		}

		return $sqlParts;
	}
}
