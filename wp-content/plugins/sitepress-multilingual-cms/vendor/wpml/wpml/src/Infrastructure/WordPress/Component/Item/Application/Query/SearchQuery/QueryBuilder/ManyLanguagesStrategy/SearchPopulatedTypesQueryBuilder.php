<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy;

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
    $sourceLanguage = $criteria->getSourceLanguageCode();

    $targetLanguageCodes       = $this->escapeTargetLanguages( $criteria->getTargetLanguageCodes() );
    $gluedEscapedLanguageCodes = implode( ',', $targetLanguageCodes );

    $elementType = $this->queryPrepare->prepare( $postTypeId );
    $sql         = "
      SELECT p.post_type 
      FROM {$this->queryPrepare->prefix()}posts p
      
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations source_t
        ON source_t.element_id = p.ID
          AND source_t.element_type = CONCAT('post_', p.post_type)
          AND source_t.language_code = '{$sourceLanguage}'
      
      LEFT JOIN wp_icl_translations target_t
         ON target_t.trid = source_t.trid
             AND target_t.language_code IN ({$gluedEscapedLanguageCodes})
       LEFT JOIN wp_icl_translation_status target_ts
         ON target_ts.translation_id = target_t.translation_id
      
      WHERE
          {$this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes )}
          {$this->buildPostStatusCondition($criteria->getPublicationStatus())} 
          AND p.post_type = '{$elementType}'
      LIMIT 0,1
    ";

    return $sql;
  }


  /**
   * @param string[] $targetLanguageCodes
   *
   * @return string[]
   */
  private function escapeTargetLanguages( array $targetLanguageCodes ): array {
    $escapedLanguageCodes = array_map(
      function ( $languageCode ) {
        return $this->queryPrepare->prepare( '%s', $languageCode );
      },
      $targetLanguageCodes
    );

    return $escapedLanguageCodes;
  }


}
