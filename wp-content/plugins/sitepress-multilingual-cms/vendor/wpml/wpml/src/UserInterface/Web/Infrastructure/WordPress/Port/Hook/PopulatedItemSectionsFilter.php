<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Port\Hook;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections\PopulatedItemSectionsFilterInterface;

class PopulatedItemSectionsFilter implements PopulatedItemSectionsFilterInterface {
  const NAME = 'wpml_tm_populated_item_sections';


  /**
   * @param string[]                     $itemSectionIds
   * @param SearchPopulatedTypesCriteria $searchCriteria
   *
   * @return string[]
   */
  public function filter( array $itemSectionIds, SearchPopulatedTypesCriteria $searchCriteria ) {
    return apply_filters(
      static::NAME,
      $itemSectionIds,
      $searchCriteria->getPublicationStatus(),
      $searchCriteria->getSourceLanguageCode(),
      current( $searchCriteria->getTargetLanguageCodes() ),
      $searchCriteria->getTranslationStatuses()
    );

  }


}
