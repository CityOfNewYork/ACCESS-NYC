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

}