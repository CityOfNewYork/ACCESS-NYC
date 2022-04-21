<?php


namespace WPML\TM\Jobs;

class Utils {

	/**
	 * Inserts an element into an array, nested by keys.
	 * Input ['a', 'b'] for the keys, an empty array for $array and $x for the value would lead to
	 * [ 'a' => ['b' => $x ] ] being returned.
	 *
	 * @param string[] $keys indexes ordered from highest to lowest level.
	 * @param mixed[]  $array array into which the value is to be inserted.
	 * @param mixed    $value to be inserted.
	 *
	 * @return mixed[]
	 */
	public static function insertUnderKeys( $keys, $array, $value ) {
		$array[ $keys[0] ] = count( $keys ) === 1
			? $value
			: self::insertUnderKeys(
				array_slice( $keys, 1 ),
				( isset( $array[ $keys[0] ] ) ? $array[ $keys[0] ] : [] ),
				$value
			);

		return $array;
	}

}
