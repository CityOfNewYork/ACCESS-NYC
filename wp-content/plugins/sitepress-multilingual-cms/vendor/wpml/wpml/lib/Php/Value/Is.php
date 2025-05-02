<?php

namespace WPML\PHP\Value;

use WPML\PHP\Exception\InvalidArgumentException;


class Is {


  /**
   * @param mixed $value
   *
   * @return bool
   *
   * @psalm-assert-if-true string $value
   */
  public static function string( $value ) {
    try {
      Validate::string( $value );
    } catch ( InvalidArgumentException $e ) {
      return false;
    }

    return true;
  }


  /**
   * @param mixed $value
   *
   * @return bool
   *
   * @psalm-assert-if-true string $value
   */
  public static function nonEmptyString( $value ) {
    try {
      Validate::nonEmptyString( $value );
    } catch ( InvalidArgumentException $e ) {
      return false;
    }

    return true;
  }


  /**
   * @param mixed $value
   *
   * @return bool
   *
   * @psalm-assert-if-true int $value
   */
  public static function int( $value ) {
    try {
      $value = Internal::getValueFromArray( $value );
    } catch ( InvalidArgumentException $e ) {
      return false;
    }

    return is_int( $value );
  }


  /**
   * @param mixed $value
   * @param callable(mixed):bool $isType
   *
   * @return bool
   *
   * @psalm-assert-if-true array $value
   */
  public static function arrayOfSameType( $value, $isType ) {
    try {
      Validate::arrayOfSameType( $value, $isType );
    } catch ( InvalidArgumentException $e ) {
      return false;
    }

    return true;
  }


  /**
   * @param mixed $value
   * @param array<string, callable(mixed):bool> $structure
   *
   * @return bool
   *
   * @psalm-assert-if-true array $value
   */
  public static function array( $value, $structure ) {
    try {
      Validate::array( $value, $structure );
    } catch ( InvalidArgumentException $e ) {
      return false;
    }

    return true;
  }


}
