<?php

namespace WPML\Core\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\HierarchicalPostCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\HierarchicalPostDto;

interface HierarchicalPostQueryInterface {


  /**
   * @param HierarchicalPostCriteria $criteria
   *
   * @return HierarchicalPostDto[]
   */
  public function getMany( HierarchicalPostCriteria $criteria );


}
