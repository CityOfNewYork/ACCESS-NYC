<?php

namespace Wordfence\MmdbReader;

class IntegerParser {

	public static function parseUnsigned($handle, $length) {
		$value = 0;
		for ($i = 0; $i < $length; $i++) {
			$byte = $handle->readByte();
			$value = ($value << 8) + $byte;
		}
		return $value;
	}

}