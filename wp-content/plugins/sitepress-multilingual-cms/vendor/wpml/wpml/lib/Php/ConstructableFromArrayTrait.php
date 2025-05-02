<?php

namespace WPML\PHP;

use ReflectionException;
use ReflectionMethod;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @template ReturnTypeFromArray
 */
trait ConstructableFromArrayTrait {


  /**
   * @phpstan-ignore-next-line Mixed array as input.
   * @param array $array
   *
   * @throws Exception If the constructor is not accessible.
   * @throws InvalidArgumentException If a required argument is missing.
   *
   * @return ReturnTypeFromArray
   */
  public static function fromArray( $array ) {
    try {
      $reflectionMethod = new ReflectionMethod( static::class, '__construct' );
      $reflectionParameterList = $reflectionMethod->getParameters();
    } catch ( ReflectionException $e ) {
      throw new Exception(
        "Can't instantiate '" . static::class . " from an array"
        . " because the constructor is not accessible."
      );
    }

    $argumentList = [];
    foreach ( $reflectionParameterList as $reflectionParameter ) {
      $parameterName = $reflectionParameter->getName();
      if (
          ! \array_key_exists( $parameterName, $array )
          && ! $reflectionParameter->isOptional()
      ) {
        throw new InvalidArgumentException(
          "Can't instantiate '" . static::class . " from an array because"
          . " argument '$parameterName' is missing and is not optional."
          . " Available arguments: " . implode( ', ', \array_keys( $array ) )
        );
      }

      $argument = $array[$parameterName]
      ?? $reflectionParameter->getDefaultValue();
      if ( $reflectionParameter->isVariadic() && \is_array( $argument ) ) {
        $argumentList = \array_merge( $argumentList, $argument );
      } else {
        $argumentList[] = $argument;
      }
    }

    return new static( ...$argumentList );
  }


}
