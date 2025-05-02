<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchPopulatedTypesQueryBuilderInterface;

class SearchPopulatedTypesQueryBuilder implements SearchPopulatedTypesQueryBuilderInterface {
  use SearchQueryBuilderTrait;

  /** @var QueryPrepareInterface */
  private $queryPrepare;


  public function __construct( QueryPrepareInterface $queryPrepare ) {
    $this->queryPrepare = $queryPrepare;
  }


  public function build( SearchPopulatedTypesCriteria $criteria, string $postTypeId ): string {
    $sourceLanguage      = $criteria->getSourceLanguageCode();
    $targetLanguageCodes = $criteria->getTargetLanguageCodes();

    $escapedLanguageCodes = $this->escapeTargetLanguages( $targetLanguageCodes );

    $elementType = $this->queryPrepare->prepare( $postTypeId );
    $sql         = "
      SELECT p.post_type 
      FROM {$this->queryPrepare->prefix()}posts p
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations source_t
        ON source_t.element_id = p.ID
          AND source_t.element_type = CONCAT('post_', p.post_type)
          AND source_t.language_code = '{$sourceLanguage}'
      {$this->buildTargetLanguageJoins($escapedLanguageCodes)}
      WHERE
          {$this->buildTranslationStatusCondition( $criteria, $escapedLanguageCodes )}
          {$this->buildPostStatusCondition($criteria->getPublicationStatus())} 
          AND p.post_type = '{$elementType}'
      LIMIT 0,1
    ";

    return $sql;
  }


  /**
   * @param array<string> $targetLanguageCodes
   *
   * @return string
   */
  private function buildTargetLanguageJoins( array $targetLanguageCodes ): string {
    $joins = [];
    foreach ( $targetLanguageCodes as $languageCode ) {
      $slugLanguageCode = $this->getLanguageJoinColumName( $languageCode );

      $joins[] = "
        LEFT JOIN {$this->queryPrepare->prefix()}icl_translations target_t_{$slugLanguageCode}
          ON target_t_{$slugLanguageCode}.trid = source_t.trid
              AND target_t_{$slugLanguageCode}.language_code = '{$languageCode}'
        LEFT JOIN {$this->queryPrepare->prefix()}icl_translation_status target_ts_{$slugLanguageCode}
          ON target_ts_{$slugLanguageCode}.translation_id = target_t_{$slugLanguageCode}.translation_id
      ";
    }

    return implode( ' ', $joins );
  }


  /**
   * @param string[] $targetLanguageCodes
   *
   * @return string[]
   */
  private function escapeTargetLanguages( array $targetLanguageCodes ): array {
    $escapedLanguageCodes = array_map(
      function ( $languageCode ) {
        return $this->queryPrepare->prepare( $languageCode );
      },
      $targetLanguageCodes
    );

    return $escapedLanguageCodes;
  }


}
