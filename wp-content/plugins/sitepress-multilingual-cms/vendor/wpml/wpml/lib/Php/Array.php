<?php

// @codingStandardsIgnoreStart
namespace WPML\PHP;
// @codingStandardsIgnoreEnd


/**
 * @param string[]             $keys
 * @param array<string, mixed> $array
 */
function array_keys_exists( array $keys, array $array ): bool {
  foreach ( $keys as $key ) {
    if ( ! array_key_exists( $key, $array ) ) {
      return false;
    }
  }

  return true;
}

/**
 * @template T
 * @param array<array-key, T> $array
 * @param callable(T, array-key): bool $callback
 *
 * @return array{0: array<array-key, T>, 1: array<array-key, T>}
 */
function partition(array $array, callable $callback): array {
  $partitions = [[], []];

  foreach ($array as $key => $value) {
    if ($callback($value, $key)) {
      $partitions[0][$key] = $value;
    } else {
      $partitions[1][$key] = $value;
    }
  }

  return $partitions;
}

/**
 * @template T
 * @param array<array-key, T|array<array-key, T>> $array
 *
 * @return array<array-key, T>
 */
function flatten(array $array): array {
  $result = [];

  foreach ($array as $value) {
    if (is_array($value)) {
      $result = array_merge($result, flatten($value));
    } else {
      $result[] = $value;
    }
  }

  return $result;
}