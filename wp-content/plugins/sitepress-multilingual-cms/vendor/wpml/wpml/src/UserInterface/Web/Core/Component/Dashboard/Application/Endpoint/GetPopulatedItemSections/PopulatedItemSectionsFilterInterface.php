<?php
namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;

interface PopulatedItemSectionsFilterInterface {


  /**
   * @param string[] $itemSectionIds
   * @param SearchPopulatedTypesCriteria $searchCriteria
   * @return string[]
   */
  public function filter( array $itemSectionIds, SearchPopulatedTypesCriteria $searchCriteria );


}
