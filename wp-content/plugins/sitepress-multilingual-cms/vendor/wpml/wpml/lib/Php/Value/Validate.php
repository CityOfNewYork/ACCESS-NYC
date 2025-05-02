<?php

namespace WPML\PHP\Value;

use WPML\PHP\Exception\InvalidArgumentException;


class Validate {


  /**
   * @template T
   *
   * @param mixed $value
   * @param T $fallback
   *
   * @throws InvalidArgumentException
   *
   * @psalm-return ($fallback is Internal::THROW_EXCEPTION ? string : string|T)
   */
  public static function string( $value, $fallback = Internal::THROW_EXCEPTION ) {
    $valueToCheck = Internal::getValueFromArray( $value );

    if ( $valueToCheck === Internal::KEY_DOES_NOT_EXIST ) {
      return Internal::fallbackOrException( $fallback, Internal::msgKeyDoesNotExist( $value ) );
    }

    if ( ! is_string( $valueToCheck ) ) {
      return Internal::fallbackOrException( $fallback, "Value is not a string." );
    }

    return $valueToCheck;
  }


  /**
   * @template T
   * @param mixed $value
   * @param T $fallback
   *
   * @throws InvalidArgumentException
   *
   * @psalm-return ($fallback is Internal::THROW_EXCEPTION ? string : string|T)
   */
  public static function nonEmptyString( $value, $fallback = Internal::THROW_EXCEPTION ) {
    $valueOfArray = Internal::getValueFromArray( $value );

    if ( $valueOfArray === Internal::KEY_DOES_NOT_EXIST ) {
      return Internal::fallbackOrException( $fallback, Internal::msgKeyDoesNotExist( $value ) );
    }

    if ( ! is_string( $valueOfArray ) || trim( $valueOfArray ) === '' ) {
      return Internal::fallbackOrException( $fallback, "Value is not a non-empty string." );
    }

    return $valueOfArray;
  }


  /**
   * This function validates if the value is an integer value.
   * It also accepts a string which has the same value when casted to an integer.
   * For example, '123' is valid (and returned as int), but '123.45' is not.
   *
   * @template T
   *
   * @param mixed $value
   * @param T $fallback
   *
   * @throws InvalidArgumentException
   *
   * @psalm-return ($fallback is Internal::THROW_EXCEPTION ? int : int|T)
   */
  public static function int( $value, $fallback = Internal::THROW_EXCEPTION ) {
    $value = Internal::getValueFromArray( $value );

    if ( ! is_numeric( $value ) ) {
      return Internal::fallbackOrException( $fallback, "Value is not an integer." );
    }

    $intValue = (int) $value;

    // phpcs:ignore
    if ( $intValue != $value ) {
      return Internal::fallbackOrException( $fallback, "Value is not an integer." );
    }

    return $intValue;
  }


  /**
   * @template R
   * @template T
   *
   * @param mixed $value
   * @param array<string, callable(mixed):R> $structure
   * @param T $fallback
   *
   * @throws InvalidArgumentException
   *
   * @psalm-assert-if-true array $value
   * @psalm-return ($fallback is Internal::THROW_EXCEPTION ? array<R> : T)
   */
  public static function array( $value, $structure, $fallback = Internal::THROW_EXCEPTION ) {
    $value = Internal::getValueFromArray( $value );

    if ( ! is_array( $value ) ) {
      return Internal::fallbackOrException( $fallback, "Value is not an array." );
    }

    $array = [];
    foreach ( $structure as $key => $validateType ) {
      if (
        substr( $key, 0, 1 ) === '?'
        && ! isset( $value[ substr( $key, 1 ) ] )
      ) {
        // Optional key, which does not exist.
        continue;
      } elseif ( substr( $key, 0, 1 ) === '?' ) {
        // Optional key, which exists.
        $key = substr( $key, 1 );
      }

      if ( ! isset( $value[ $key ] ) || ! $validateType( $value[ $key ] ) ) {
        return Internal::fallbackOrException( $fallback, "Value is not an array with the correct structure." );
      }

      $array[ $key ] = $value[ $key ];
    }

    return $value;
  }


  /**
   * @template R
   * @template T
   *
   * @param mixed $value
   * @param callable(mixed):R $validateType
   * @param T $fallback
   *
   * @throws InvalidArgumentException
   *
   * @psalm-return ($fallback is Internal::THROW_EXCEPTION ? array<R> : T)
   */
  public static function arrayOfSameType( $value, $validateType, $fallback = Internal::THROW_EXCEPTION ) {
    $value = Internal::getValueFromArray( $value );

    if ( ! is_array( $value ) ) {
      return Internal::fallbackOrException( $fallback, "Value is not an array." );
    }

    $arrayOfSameType = [];

    foreach ( $value as $item ) {
      $result = $validateType( $item );
      if ( $result === false ) {
        return Internal::fallbackOrException( $fallback, "Value is not an array of the same type." );
      }
      $arrayOfSameType[] = is_bool( $result ) ? $item : $result;
    }

    return $arrayOfSameType;
  }


}
