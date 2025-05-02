<?php

namespace WPML\Core\Port\Persistence\Exception;

use WPML\PHP\Exception\Exception;

class CanOnlyHydrateFromArrayException extends Exception {


  /**
   * @param mixed $item
   */
  public function __construct( $item ) {
    parent::__construct(
      "Can only hydrate to object from an array, '" .
        $this->getType( $item ) . "' given."
    );
  }


  /**
   * @param mixed $item
   */
  private function getType( $item ): string {
    if ( is_object( $item ) ) {
      return \get_class( $item );
    }

    return gettype( $item );
  }


}
