<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query\ManyLanguagesStrategy;

use WPML\StringTranslation\Application\StringPackage\Query\Criteria\SearchPopulatedKindsCriteria;
use WPML\StringTranslation\Application\StringPackage\Query\Criteria\StringPackageCriteria;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

trait TranslationStatusQueryBuilderTrait {
	/**
	 * @param StringPackageCriteria|SearchPopulatedKindsCriteria $criteria
	 * @param array<string>                                      $targetLanguageCodes
	 *
	 * @return string
	 */
	protected function buildTranslationStatusCondition( $criteria, array $targetLanguageCodes ): string {
		$gluedLanguageCodes = wpml_prepare_in( $targetLanguageCodes, '%s' );

		$isNotTranslated = in_array( TranslationStatus::NOT_TRANSLATED, $criteria->getTranslationStatuses() );
		$needsUpdate     = in_array( TranslationStatus::NEEDS_UPDATE, $criteria->getTranslationStatuses() );
		$complete        = in_array( TranslationStatus::COMPLETE, $criteria->getTranslationStatuses() );

		$statuses = $this->getStatusesToQuery( $criteria );

		$appendConditions     = [];
		$targetLanguagesCount = count( $targetLanguageCodes );

		if ( $isNotTranslated ) {
			$translationStatuses = wpml_prepare_in(
				[
					ICL_TM_NOT_TRANSLATED,
					ICL_TM_NEEDS_UPDATE,
					ICL_TM_NEEDS_REVIEW,
					ICL_TM_WAITING_FOR_TRANSLATOR,
					ICL_TM_IN_PROGRESS,
				],
				'%d'
			);

			// Skip the string packages with status 0 (canceled jobs) but already translated.
			$appendConditions[] = "
						        (
						          SELECT COUNT(DISTINCT t.language_code)
						          FROM {$this->wpdb->prefix}icl_translations t
						          INNER JOIN {$this->wpdb->prefix}icl_translation_status ts
						              ON ts.translation_id = t.translation_id
						          WHERE t.trid = source_t.trid
						              AND t.language_code IN ({$gluedLanguageCodes})
						              AND t.element_id IS NULL
						              AND ts.status NOT IN ({$translationStatuses})
						        ) < {$targetLanguagesCount} 
						      ";
		}
		if ( $needsUpdate ) {
			$appendConditions[] = 'target_ts.needs_update = 1';
		}
		if ( $complete ) {
			// We want to display string packages with canceled jobs (status 0) but already translated.
			$appendConditions[] = '(target_t.element_id IS NULL AND ((target_ts.status = 10) OR (target_ts.status = 0 OR target_ts.status IS NULL)))';
		}

		if ( ! empty( $statuses ) ) {
			$appendConditions[] = sprintf(
				'target_ts.status IN %s' .
				// Make sure "needs_update" is 0 for filter "Translation complete".
				( $complete ? ' AND target_ts.needs_update = 0' : '' ),
				'(' . implode( ', ', $statuses ) . ')'
			);
		}

		if ( ! empty( $appendConditions ) ) {
			return '(' . implode( ' OR ', $appendConditions ) . ')';
		}

		return '1';
	}

	/**
	 * @param StringPackageCriteria|SearchPopulatedKindsCriteria $criteria
	 *
	 * @return array<string>
	 */
	private function getStatusesToQuery( $criteria ) {
		$statuses = [];
		foreach ( $criteria->getTranslationStatuses() as $status ) {
			if ( in_array( $status, [ TranslationStatus::NOT_TRANSLATED, TranslationStatus::NEEDS_UPDATE ] ) ) {
				continue;
			}
			$statuses[] = $status;
		}

		return $statuses;
	}
}