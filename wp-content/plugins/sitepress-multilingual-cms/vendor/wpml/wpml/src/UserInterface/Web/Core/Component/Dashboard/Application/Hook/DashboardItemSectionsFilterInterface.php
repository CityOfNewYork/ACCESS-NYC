<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Hook;

use WPML\UserInterface\Web\Core\Component\Dashboard\Application\ViewModel\ItemSection;

/**
 * @phpstan-import-type ItemSectionData from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\ViewModel\ItemSection
 */
interface DashboardItemSectionsFilterInterface {


  /**
   * @param ItemSection[] $itemSections
   *
   * @return ItemSection[]
   */
  public function filter( array $itemSections );


  /**
   * @param ItemSectionData[] $itemSections
   *
   * @return ItemSectionData[]
   */
  public function addNoteToSections( array $itemSections ): array;


}
