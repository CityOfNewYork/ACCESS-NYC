<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\ItemLanguageQueryInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;

class RegularItemsAndStringsLanguageQuery implements ItemLanguageQueryInterface {

  /** @var StringLanguageQuery */
  private $stringLanguageQuery;

  /** @var ItemLanguageQuery */
  private $itemLanguageQuery;


  public function __construct( StringLanguageQuery $stringLanguageQuery, ItemLanguageQuery $itemLanguageQuery ) {
    $this->stringLanguageQuery = $stringLanguageQuery;
    $this->itemLanguageQuery   = $itemLanguageQuery;
  }


  public function getManyOriginalLanguagesOfItems( array $items ): array {
    $stringItems = array_filter(
      $items,
      function ( $item ) {
        return $item['type']->get() === TranslationType::STRING;
      }
    );

    $regularItems = array_filter(
      $items,
      function ( $item ) {
        return $item['type']->get() !== TranslationType::STRING;
      }
    );

    $stringLanguages  = $this->stringLanguageQuery->getManyOriginalLanguagesOfItems( $stringItems );
    $regularLanguages = $this->itemLanguageQuery->getManyOriginalLanguagesOfItems( $regularItems );

    return array_merge( $stringLanguages, $regularLanguages );
  }


}
