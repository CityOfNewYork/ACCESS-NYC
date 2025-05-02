<?php

namespace WPML\Infrastructure\WordPress\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\ItemLanguageQueryInterface;
use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\Core\SharedKernel\Component\String\Application\Query\StringLanguageQueryInterface;

class StringLanguageQuery implements ItemLanguageQueryInterface {

  /** @var StringLanguageQueryInterface */
  private $stringLanguageQuery;


  public function __construct( StringLanguageQueryInterface $stringLanguageQuery ) {
    $this->stringLanguageQuery = $stringLanguageQuery;
  }


  /**
   * @param array{itemId: int, type: TranslationType}[] $items
   *
   * @return array{itemId: int, type: TranslationType, language: string}[]
   */
  public function getManyOriginalLanguagesOfItems( array $items ): array {
      $stringItems = array_filter(
        $items,
        function ( $item ) {
          return $item['type']->get() === TranslationType::STRING;
        }
      );

    if ( ! $stringItems ) {
        return [];
    }

      $stringIds = array_map(
        function ( $item ) {
          return $item['itemId'];
        },
        $stringItems
      );

      $stringLanguages = $this->stringLanguageQuery->getStringLanguages( $stringIds );

      return array_map(
        function ( $item ) use ( $stringLanguages ) {
          return [
          'itemId'   => $item['itemId'],
          'type'     => $item['type'],
          'language' => $stringLanguages[ $item['itemId'] ] ?? 'en',
          ];
        },
        $stringItems
      );
  }


}
