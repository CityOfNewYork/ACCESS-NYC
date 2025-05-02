<?php

namespace WPML\PHP;

/**
 * @template ReturnTypeFromArray
 */
interface ConstructableFromArrayInterface {


  /**
    * @phpstan-ignore-next-line Mixed array as input.
    *
    * @param array $array
    * @return ReturnTypeFromArray
    */
  public static function fromArray( $array );


}
