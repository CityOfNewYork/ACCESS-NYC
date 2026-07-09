<?php

namespace WordfenceLS;

class Utility_Number {
	public static function isInteger($value, $min = null, $max = null) {
		$options = array();
		if ($min !== null)
			$options['min_range'] = $min;
		if ($max !== null)
			$options['max_range'] = $max;
		return filter_var($value, FILTER_VALIDATE_INT, array('options' => $options)) !== false;
	}

	public static function isUnixTimestamp($value) {
		return self::isInteger($value, 0);
	}
	
	/**
	 * Translates a value to a boolean, correctly interpreting various textual representations.
	 *
	 * @param $value
	 * @return bool
	 */
	public static function truthyToBool($value) {
		if ($value === true || $value === false) {
			return $value;
		}
		
		if (is_null($value)) {
			return false;
		}
		
		if (is_numeric($value)) {
			return !!$value;
		}
		
		if (preg_match('/^(?:f(?:alse)?|no?|off)$/i', $value)) {
			return false;
		}
		else if (preg_match('/^(?:t(?:rue)?|y(?:es)?|on)$/i', $value)) {
			return true;
		}
		
		return !empty($value);
	}
}