<?php

namespace WordfenceLS;

class Utility_Array {

	public static function findOffset($array, $key) {
		$offset = 0;
		foreach ($array as $index => $value) {
			if ($index === $key)
				return $offset;
			$offset++;
		}
		return null;
	}

	public static function insertAfter(&$array, $targetKey, $key, $value) {
		$offset = self::findOffset($array, $targetKey);
		if ($offset === null)
			return false;
		$array = array_merge(
			array_slice($array, 0, $offset + 1),
			array( $key => $value ),
			array_slice($array, $offset + 1)
		);
		return true;
	}
	
	/**
	 * Returns the items from $array whose keys are in $keys.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @param bool $single Return single-value as-is instead of a one-element array.
	 * @param mixed|null $default Value to return when $single is true and nothing is found.
	 * @return array|mixed
	 */
	public static function arrayChoose($array, $keys, $single = false, $default = null) {
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		
		$matches = array_filter($array, function($k) use ($keys) {
			return in_array($k, $keys);
		}, ARRAY_FILTER_USE_KEY);
		if ($single) {
			$key = self::arrayFirst($keys);
			if ($key !== null && isset($matches[$key])) {
				return $matches[$key];
			}
			
			return $default;
		}
		return $matches;
	}
	
	/**
	 * Convenience function for `arrayChoose` in its single return value mode for better code readability.
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed
	 */
	public static function arrayGet($array, $key, $default = null) {
		return self::arrayChoose($array, $key, true, $default);
	}
	
	public static function arrayFirst($array) {
		if (empty($array)) {
			return null;
		}
		
		$values = array_values($array);
		return $values[0];
	}
	
	public static function arrayLast($array) {
		if (empty($array)) {
			return null;
		}
		
		$values = array_values($array);
		return $values[count($values) - 1];
	}
}