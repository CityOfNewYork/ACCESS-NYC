<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;

interface SearchQueryBuilderInterface {


  public function build( SearchCriteria $criteria ): string;


  public function buildCount( SearchCriteria $criteria ): string;


}
