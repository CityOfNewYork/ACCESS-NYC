<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchCriteriaQueryBuilder;

use WPML\Core\Component\Post\Application\Query\Criteria\SortingCriteria;

class SortingCriteriaQueryBuilder {

  const DEFAULT_SORTING_QUERY_PART = 'ORDER BY p.post_date %s';

  const SORT_BY_TITLE_QUERY_PART = 'ORDER BY p.post_title %s';


  /**
   * @phpstan-param  SortingCriteria | null $sortingCriteria
   *
   * @return string
   */
  public function build( $sortingCriteria ): string {
    if ( ! $sortingCriteria ) {
      return $this->getDefaultSortingQueryPart();
    }

    if ( $sortingCriteria->getSortBy() === 'title' ) {
      return sprintf( self::SORT_BY_TITLE_QUERY_PART, $sortingCriteria->getSortingOrder() );
    }

    return sprintf( self::DEFAULT_SORTING_QUERY_PART, $sortingCriteria->getSortingOrder() );
  }


  public function getDefaultSortingQueryPart(): string {
    return sprintf(
      self::DEFAULT_SORTING_QUERY_PART,
      SortingCriteria::DEFAULT_SORTING_DIRECTION
    );
  }


}
