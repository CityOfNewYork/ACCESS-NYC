<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

trait SearchQueryBuilderTrait {


  /**
   * @param SearchCriteria|SearchPopulatedTypesCriteria $criteria
   * @param array<string>                               $targetLanguageCodes
   *
   * @return string
   */
  protected function buildTranslationStatusCondition( $criteria, array $targetLanguageCodes ): string {
    $gluedLanguageCodes = implode( ', ', $targetLanguageCodes );

    $isNotTranslated = in_array( TranslationStatus::NOT_TRANSLATED, $criteria->getTranslationStatuses() );
    $needsUpdate     = in_array( TranslationStatus::NEEDS_UPDATE, $criteria->getTranslationStatuses() );
    $complete        = in_array( TranslationStatus::COMPLETE, $criteria->getTranslationStatuses() );

    $statuses = $this->getStatusesToQuery( $criteria );

    $appendConditions     = [];
    $targetLanguagesCount = count( explode( ',', $gluedLanguageCodes ) );

    if ( $isNotTranslated ) {
      // Skip the posts with status 0 (canceled jobs) but already translated.
      $appendConditions[] = "
        (
          SELECT COUNT(DISTINCT t.language_code)
          FROM {$this->queryPrepare->prefix()}icl_translations t
          WHERE t.trid = source_t.trid
              AND t.language_code IN ({$gluedLanguageCodes})
              AND t.element_id IS NOT NULL
        ) < {$targetLanguagesCount} 
      ";
    }
    if ( $needsUpdate ) {
      $appendConditions[] = 'target_ts.needs_update = 1';
    }
    if ( $complete ) {
      // We want to display posts with canceled jobs (status 0) but already translated.
      $appendConditions[] = '(target_t.element_id IS NOT NULL AND (target_ts.status = 0 OR target_ts.status IS NULL))';
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
   * @param SearchCriteria|SearchPopulatedTypesCriteria $criteria
   *
   * @return array<string>
   */
  private function getStatusesToQuery( $criteria ) {
    $statuses = [];
    foreach ( $criteria->getTranslationStatuses() as $status ) {
      if ( in_array( $status, [ TranslationStatus::NOT_TRANSLATED, TranslationStatus::NEEDS_UPDATE ] ) ) {
        continue;
      }
      $statuses[] = $this->queryPrepare->prepare( (string) $status );
    }

    return $statuses;
  }


  /**
   * @param string|null $status
   *
   * @return string
   */
  protected function buildPostStatusCondition( $status ): string {
    $statusQuery = "AND p.post_status NOT IN ('auto-draft', 'trash')";
    $statusQuery .= $status ? $this->queryPrepare->prepare( ' AND p.post_status = %s', $status ) : '';

    return $statusQuery;
  }


  protected function getLanguageJoinColumName( string $languageCode ): string {
    return preg_replace( '/[^a-zA-Z0-9]/', '_', $languageCode ) ?: $languageCode;
  }


}
