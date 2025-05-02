<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;

interface SearchPopulatedTypesQueryBuilderInterface {


  public function build( SearchPopulatedTypesCriteria $criteria, string $postTypeId ): string;


}
