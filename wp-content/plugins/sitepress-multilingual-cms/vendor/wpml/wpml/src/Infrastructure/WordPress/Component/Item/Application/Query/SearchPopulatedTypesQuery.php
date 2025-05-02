<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria as SearchCriteria;
use WPML\Core\Component\Post\Application\Query\SearchPopulatedTypesQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\SearchQuery\QueryBuilder\QueryBuilderResolver;

class SearchPopulatedTypesQuery implements SearchPopulatedTypesQueryInterface {

  /** @var QueryBuilderResolver */
  private $queryBuilderResolver;

  /** @var QueryHandlerInterface<int, string> $queryHandler */
  private $queryHandler;


  /**
   * @param QueryBuilderResolver               $queryBuilderResolver
   * @param QueryHandlerInterface<int, string> $queryHandler
   */
  public function __construct(
    QueryBuilderResolver $queryBuilderResolver,
    QueryHandlerInterface $queryHandler
  ) {
    $this->queryBuilderResolver = $queryBuilderResolver;
    $this->queryHandler         = $queryHandler;
  }


  /**
   * @throws DatabaseErrorException
   */
  public function get( SearchCriteria $criteria ): array {
    // We're going to run the query, per post type.
    $postTypes = $criteria->getPostTypeIds();
    foreach ( $postTypes as $postTypeIndex => $postType ) {
      $query = $this->queryBuilderResolver
          ->resolveSearchPopulatedTypesQueryBuilder()
          ->build( $criteria, $postType );

      if ( ! $this->queryHandler->querySingle( $query ) ) {
        unset( $postTypes[ $postTypeIndex ] );
      }
    }

    return $postTypes;
  }


}
