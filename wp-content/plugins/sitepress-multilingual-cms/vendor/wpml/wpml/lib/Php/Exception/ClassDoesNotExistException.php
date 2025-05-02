<?php

namespace WPML\PHP\Exception;

class ClassDoesNotExistException extends Exception {


  public function __construct( string $classname ) {
    parent::__construct( "Class $classname does not exist." );
  }


}
