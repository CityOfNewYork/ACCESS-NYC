<?php

namespace WPML\Core\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Domain\TranslationType;

interface ItemLanguageQueryInterface {


  /**
   * @param array{itemId: int, type: TranslationType}[] $items
   *
   * @return array{itemId: int, type: TranslationType, language: string}[]
   */
  public function getManyOriginalLanguagesOfItems( array $items ): array;


}
