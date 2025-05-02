<?php

namespace WPML\Core\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostWithTranslationStatusDto;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\ResultCollectionInterface;

/**
 * The query build is completely dependent of the infrastructure layer
 * as we don't have an abstraction of query building (like Doctrine).
 * Therefore, we only have this interface on Core to define what the
 * Infrastructure must return (PostWithTranslationStatusDto).
 */
interface SearchQueryInterface {


  /**
   * @param SearchCriteria $criteria
   *
   * @return ResultCollectionInterface<int,PostWithTranslationStatusDto>
   * @throws DatabaseErrorException
   */
  public function get( SearchCriteria $criteria );


  /**
   * @param SearchCriteria $criteria
   *
   * @return int
   * @throws DatabaseErrorException
   */
  public function count( SearchCriteria $criteria ): int;


}
