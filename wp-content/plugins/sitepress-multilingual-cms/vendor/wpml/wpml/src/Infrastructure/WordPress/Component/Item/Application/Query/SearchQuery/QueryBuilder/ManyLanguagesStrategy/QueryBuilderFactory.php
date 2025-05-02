<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\ManyLanguagesStrategy;

use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchPopulatedTypesQueryBuilderInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\SearchQueryBuilderInterface;

class QueryBuilderFactory {

  /** @var SearchQueryBuilder */
  private $searchQueryBuilder;

  /** @var SearchPopulatedTypesQueryBuilder */
  private $searchPopulatedTypesQueryBuilder;


  public function __construct(
    SearchQueryBuilder $searchQueryBuilder,
    SearchPopulatedTypesQueryBuilder $searchPopulatedTypesQueryBuilder
  ) {
    $this->searchQueryBuilder               = $searchQueryBuilder;
    $this->searchPopulatedTypesQueryBuilder = $searchPopulatedTypesQueryBuilder;
  }


  public function createSearchQueryBuilder(): SearchQueryBuilderInterface {
    return $this->searchQueryBuilder;
  }


  public function createSearchPopulatedTypesQueryBuilder(): SearchPopulatedTypesQueryBuilderInterface {
    return $this->searchPopulatedTypesQueryBuilder;
  }


}
