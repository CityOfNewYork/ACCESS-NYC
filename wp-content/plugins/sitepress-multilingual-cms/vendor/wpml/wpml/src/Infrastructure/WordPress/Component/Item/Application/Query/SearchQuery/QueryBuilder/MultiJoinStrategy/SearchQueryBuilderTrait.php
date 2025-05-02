<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;

trait SearchQueryBuilderTrait {


  /**
   * @param SearchCriteria|SearchPopulatedTypesCriteria $criteria
   * @param array<string> $targetLanguageCodes
   *
   * @return string
   */
  protected function buildTranslationStatusCondition( $criteria, array $targetLanguageCodes ): string {
    $isNotTranslated = in_array( TranslationStatus::NOT_TRANSLATED, $criteria->getTranslationStatuses() );
    $needsUpdate = in_array( TranslationStatus::NEEDS_UPDATE, $criteria->getTranslationStatuses() );
    $complete = in_array( TranslationStatus::COMPLETE, $criteria->getTranslationStatuses() );

    $languagesCombinedConditions = [];
    foreach ( $targetLanguageCodes as $targetLanguageCode ) {
      $slugTargetLanguageCode =  $this->getLanguageJoinColumName( $targetLanguageCode );
      $statuses = $this->getStatusesToQuery( $criteria );

      $appendConditions = [];

      if ( $isNotTranslated ) {
        // Skip the posts with status 0 (canceled jobs) but already translated.
        $appendConditions[] = sprintf(
          'target_t_%1$s.trid IS NULL',
          $slugTargetLanguageCode
        );
      }
      if ( $needsUpdate ) {
        $appendConditions[] = sprintf( 'target_ts_%s.needs_update = 1', $slugTargetLanguageCode );
      }
      if ( $complete ) {
        // We want to display posts with canceled jobs (status 0) but already translated.
        $appendConditions[] = sprintf(
          '(target_t_%1$s.element_id IS NOT NULL AND (target_ts_%1$s.status = 0 OR target_ts_%1$s.status IS NULL))',
          $slugTargetLanguageCode
        );
      }

      if ( ! empty( $statuses ) ) {
        $appendConditions[] = sprintf(
          'target_ts_%1$s.status IN %2$s' .
            // Make sure "needs_update" is 0 for filter "Translation complete".
            ( $complete ? ' AND target_ts_%1$s.needs_update = 0' : '' ),
          $slugTargetLanguageCode,
          '(' . implode( ', ', $statuses ) . ')'
        );
      }

      if ( ! empty( $appendConditions ) ) {
        $languagesCombinedConditions[] = '(' . implode( ' OR ', $appendConditions ) . ')';
      }
    }
    if ( empty( $languagesCombinedConditions ) ) {
      return '1';
    }
    return '(' . implode( ' OR ', $languagesCombinedConditions ) . ')';
  }


  /**
   * @param SearchCriteria|SearchPopulatedTypesCriteria $criteria
   * @return array<string>
   */
  private function getStatusesToQuery( $criteria ) {
    $statuses = [];
    foreach ( $criteria->getTranslationStatuses() as $status ) {
      if ( in_array( $status, [TranslationStatus::NOT_TRANSLATED, TranslationStatus::NEEDS_UPDATE] ) ) {
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
