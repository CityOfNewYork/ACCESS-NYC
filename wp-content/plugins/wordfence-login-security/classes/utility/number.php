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

}