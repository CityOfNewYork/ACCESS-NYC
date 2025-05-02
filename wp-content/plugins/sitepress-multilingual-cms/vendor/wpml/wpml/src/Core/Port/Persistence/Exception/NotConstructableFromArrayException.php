<?php

namespace WPML\Core\Port\Persistence\Exception;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\Exception\Exception;

class NotConstructableFromArrayException extends Exception {


  public function __construct( string $classname ) {
    parent::__construct(
      "The class $classname is not constructable from an array. It must "
        . "implement interface " . ConstructableFromArrayInterface::class . "."
    );
  }


}
