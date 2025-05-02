<?php

namespace WPML\Core\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;

interface SearchPopulatedTypesQueryInterface {


  /**
   * Will get all the PostType id's that matches the Criteria.
   *
   * @param SearchPopulatedTypesCriteria $criteria
   *
   * @return array<int, string>
   * @throws DatabaseErrorException
   */
  public function get( SearchPopulatedTypesCriteria $criteria ): array;


}
