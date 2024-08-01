<?php

namespace WordfenceLS;

use RuntimeException;

class Utility_Serialization {

	public static function unserialize($data, $options = array(), $validator = null) {
		static $serializedFalse;
		if ($serializedFalse === null)
			$serializedFalse = serialize(false);
		if ($data === $serializedFalse)
			return false;
		if (!is_serialized($data))
			throw new RuntimeException('Input data is not serialized');
		if (version_compare(PHP_VERSION, '5.6', '<=')) {
			$unserialized = @unserialize($data);
		}
		else {
			$unserialized = @unserialize($data, $options);
		}
		if ($unserialized === false)
			throw new RuntimeException('Deserialization failed');
		if ($validator !== null && !$validator($unserialized))
			throw new RuntimeException('Validation of unserialized data failed');
		return $unserialized;
	}

}