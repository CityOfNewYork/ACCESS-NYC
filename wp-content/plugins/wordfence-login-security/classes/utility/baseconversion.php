<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_Base2n;

class Utility_BaseConversion {

	public static function get_base32() {
		static $base32 = null;
		if ($base32 === null)
			$base32 = new Model_Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', false, true, true);
		return $base32;
	}

	public static function base32_encode($data) {
		$base32 = self::get_base32();
		return $base32->encode($data);
	}

}