<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

final class SortingCriteria {

  const AVAILABLE_SORTING_DIRECTIONS = [ 'DESC', 'ASC' ];
  const AVAILABLE_SORTING_OPTIONS = [ 'title', 'date' ];

  const DEFAULT_SORT_BY = 'date';
  const DEFAULT_SORTING_DIRECTION = 'DESC';

  /** @var string */
  private $sortBy;

  /** @var string */
  private $sortingOrder;


  public function __construct( string $by, string $order ) {
    $this->sortBy = in_array( $by, self::AVAILABLE_SORTING_OPTIONS ) ?
      $by : self::DEFAULT_SORT_BY;

    $order = strtoupper( $order );
    $this->sortingOrder = in_array( $order, self::AVAILABLE_SORTING_DIRECTIONS ) ?
      $order : self::DEFAULT_SORTING_DIRECTION;
  }


  public function getSortBy(): string {
    return $this->sortBy;
  }


  public function getSortingOrder(): string {
    return $this->sortingOrder;
  }


}
