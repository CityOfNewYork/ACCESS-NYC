<?php

namespace WPML\Core\Component\Translation\Application\Repository;

use WPML\Core\Component\Translation\Domain\TranslationType;
use WPML\PHP\Exception\Exception;

class TranslationNotFoundException extends Exception {


  public function __construct( TranslationType $itemType, string $elementType, int $elementId ) {
    parent::__construct(
      sprintf( 'Translation not found for %s %d', $itemType->get() . '_' . $elementType, $elementId )
    );
  }


}
