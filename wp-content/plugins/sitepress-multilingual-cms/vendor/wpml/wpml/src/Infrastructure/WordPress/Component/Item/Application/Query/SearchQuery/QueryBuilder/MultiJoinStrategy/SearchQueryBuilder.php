<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\MultiJoinStrategy;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchCriteriaQueryBuilder\SortingCriteriaQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchQueryBuilderInterface;

class SearchQueryBuilder implements SearchQueryBuilderInterface {
  use SearchQueryBuilderTrait;

  const WORD_COUNT_META_KEY = '_wpml_word_count';
  const TRANSLATOR_NOTE_META_KEY = '_icl_translator_note';

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var SortingCriteriaQueryBuilder */
  private $sortingQueryBuilder;

  const POST_COLUMNS = "
    p.ID,
    p.post_title,
    p.post_status,
    p.post_date,
    p.post_type
  ";


  public function __construct(
    QueryPrepareInterface $queryPrepare,
    SortingCriteriaQueryBuilder $sortingQueryBuilder
  ) {
    $this->queryPrepare        = $queryPrepare;
    $this->sortingQueryBuilder = $sortingQueryBuilder;
  }


  public function build( SearchCriteria $criteria ): string {
    return $this->buildQueryWithFields( $criteria, $this->getFields() );
  }


  public function buildCount( SearchCriteria $criteria ): string {
    return $this->buildQueryWithFields( $criteria, 'COUNT(p.ID)', false );
  }


  private function getFields(): string {
    $postColumns = self::POST_COLUMNS;

    return "
        {$postColumns},
        IFNULL(meta_wc.meta_value, 0) AS word_count,
        meta_tn.meta_value AS translator_note        
		";
  }


  private function buildQueryWithFields(
    SearchCriteria $criteria,
    string $fields,
    bool $withPagination = true
  ): string {
    $sourceLanguage = $criteria->getSourceLanguageCode();

    $targetLanguageCodes = $criteria->getTargetLanguageCodes();

    $sql = "
      SELECT
          {$fields}
      FROM {$this->queryPrepare->prefix()}posts p
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations source_t
      ON source_t.element_id = p.ID
        AND source_t.element_type = CONCAT('post_', p.post_type)
        AND source_t.language_code = '{$sourceLanguage}'
      {$this->buildTargetLanguageJoins( $targetLanguageCodes )}
        
  
      LEFT JOIN {$this->queryPrepare->prefix()}postmeta meta_wc
        ON meta_wc.post_id = p.ID
        AND meta_wc.meta_key = '" . self::WORD_COUNT_META_KEY . "'
      LEFT JOIN {$this->queryPrepare->prefix()}postmeta meta_tn
        ON meta_tn.post_id = p.ID
        AND meta_tn.meta_key = '" . self::TRANSLATOR_NOTE_META_KEY . "'
      WHERE p.post_type = '{$criteria->getType()}'
          {$this->buildPostStatusCondition( $criteria->getPublicationStatus() )}
          {$this->buildPostTitleCondition( $criteria )}
          {$this->buildTaxonomyTermCondition( $criteria )}
          {$this->buildParentCondition( $criteria )}
          {$this->buildTranslationStatusConditionWrapper( $criteria, $targetLanguageCodes )}
          {$this->buildSortingQueryPart( $criteria )}
    "; // @codingStandardsIgnoreEnd

    if ( $withPagination ) {
      $sql .= ' ' . $this->buildPagination( $criteria );
    }

    return $sql;
  }


  private function buildSortingQueryPart( SearchCriteria $criteria ): string {
    return $this->sortingQueryBuilder->build( $criteria->getSortingCriteria() );
  }


  private function buildPostTitleCondition(
    SearchCriteria $criteria
  ): string {
    $searchKeyword = $this->queryPrepare->escLike( $criteria->getTitle() );
    if ( $searchKeyword !== '' ) {
      return $this->queryPrepare->prepare(
        'AND p.post_title LIKE %s',
        '%' . $searchKeyword . '%'
      );
    }

    return '';
  }


  /**
   * @param SearchCriteria $criteria
   * @param array<string>  $targetLanguageCodes
   *
   * @return string
   */
  private function buildTranslationStatusConditionWrapper(
    SearchCriteria $criteria,
    array $targetLanguageCodes
  ): string {
    return 'AND ' . $this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes );
  }


  private function buildTaxonomyTermCondition( SearchCriteria $criteria ): string {
    if ( ! $criteria->getTaxonomyId() || ! $criteria->getTermId() ) {
      return '';
    }

    $where = $this->queryPrepare->prepare(
      "WHERE tax.taxonomy = %s AND tax.term_id = %d",
      $criteria->getTaxonomyId(),
      $criteria->getTermId()
    );

    return " AND p.ID IN (
      SELECT object_id
      FROM {$this->queryPrepare->prefix()}term_relationships rel
      JOIN {$this->queryPrepare->prefix()}term_taxonomy tax " .
           "ON rel.term_taxonomy_id = tax.term_taxonomy_id
      $where
      ) ";
  }


  private function buildParentCondition(
    SearchCriteria $criteria
  ): string {
    if ( $criteria->getParentId() ) {
      return $this->queryPrepare->prepare(
        'AND p.post_parent = %d',
        $criteria->getParentId()
      );
    }

    return '';
  }


  private function buildPagination( SearchCriteria $criteria ): string {
    return $this->queryPrepare->prepare(
      'LIMIT %d OFFSET %d',
      $criteria->getLimit(),
      $criteria->getOffset()
    );
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


}
