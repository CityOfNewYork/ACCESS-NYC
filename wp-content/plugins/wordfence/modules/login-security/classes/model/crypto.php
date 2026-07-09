<?php

namespace WordfenceLS;

abstract class Model_Crypto {
	/**
	 * Refreshes the secrets used by the plugin.
	 */
	public static function refresh_secrets() {
		Controller_Settings::shared()->set(Controller_Settings::OPTION_SHARED_HASH_SECRET_KEY, bin2hex(self::random_bytes(32)));
		Controller_Settings::shared()->set(Controller_Settings::OPTION_SHARED_SYMMETRIC_SECRET_KEY, bin2hex(self::random_bytes(32)));
		Controller_Settings::shared()->set(Controller_Settings::OPTION_LAST_SECRET_REFRESH, Controller_Time::time(), true);
	}
	
	/**
	 * Returns the secret for hashing.
	 * 
	 * @return string
	 */
	public static function shared_hash_secret() {
		return Controller_Settings::shared()->get(Controller_Settings::OPTION_SHARED_HASH_SECRET_KEY);
	}
	
	/**
	 * Returns the secret for symmetric encryption.
	 * 
	 * @return string
	 */
	public static function shared_symmetric_secret() {
		return Controller_Settings::shared()->get(Controller_Settings::OPTION_SHARED_SYMMETRIC_SECRET_KEY);
	}
	
	/**
	 * Returns whether or not the installation has the required crypto support for this to work.
	 * 
	 * @return bool
	 */
	public static function has_required_crypto_functions() {
		if (function_exists('openssl_get_publickey') && function_exists('openssl_get_cipher_methods')) {
			$ciphers = openssl_get_cipher_methods();
			return array_search('aes-256-cbc', $ciphers) !== false;
		}
		return false;
	}
	
	/**
	 * Utility
	 */
	
	public static function random_bytes($bytes) {
		$bytes = (int) $bytes;
		if (function_exists('random_bytes')) {
			try {
				$rand = random_bytes($bytes);
				if (is_string($rand) && self::strlen($rand) === $bytes) {
					return $rand;
				}
			} catch (\Exception $e) {
				// Fall through
			} catch (\TypeError $e) {
				// Fall through
			} catch (\Error $e) {
				// Fall through
			}
		}
		if (function_exists('mcrypt_create_iv')) {
			// phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.mcrypt_create_ivDeprecatedRemoved,PHPCompatibility.Extensions.RemovedExtensions.mcryptDeprecatedRemoved,PHPCompatibility.Constants.RemovedConstants.mcrypt_dev_urandomDeprecatedRemoved
			$rand = @mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
			if (is_string($rand) && self::strlen($rand) === $bytes) {
				return $rand;
			}
		}
		if (function_exists('openssl_random_pseudo_bytes')) {
			$rand = @openssl_random_pseudo_bytes($bytes, $strong);
			if (is_string($rand) && self::strlen($rand) === $bytes) {
				return $rand;
			}
		}
		// Last resort is insecure
		$return = '';
		for ($i = 0; $i < $bytes; $i++) {
			$return .= chr(mt_rand(0, 255));
		}
		return $return;
	}
	
	/**
	 * Polyfill for random_int.
	 *
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public static function random_int($min = 0, $max = 0x7FFFFFFF) {
		if (function_exists('random_int')) {
			try {
				return random_int($min, $max);
			} catch (\Exception $e) {
				// Fall through
			} catch (\TypeError $e) {
				// Fall through
			} catch (\Error $e) {
				// Fall through
			}
		}
		$diff = $max - $min;
		$bytes = self::random_bytes(4);
		if ($bytes === false || self::strlen($bytes) != 4) {
			throw new \RuntimeException("Unable to get 4 bytes");
		}
		$val = @unpack("Nint", $bytes);
		$val = $val['int'] & 0x7FFFFFFF;
		$fp = (float) $val / 2147483647.0; // convert to [0,1]
		return (int) (round($fp * $diff) + $min);
	}
	
	public static function uuid() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			self::random_int(0, 0xffff), self::random_int(0, 0xffff),
			
			// 16 bits for "time_mid"
			self::random_int(0, 0xffff),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			self::random_int(0, 0x0fff) | 0x4000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			self::random_int(0, 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			self::random_int(0, 0xffff), self::random_int(0, 0xffff), self::random_int(0, 0xffff)
		);
	}
	
	/**
	 * Set the mbstring internal encoding to a binary safe encoding when func_overload
	 * is enabled.
	 *
	 * When mbstring.func_overload is in use for multi-byte encodings, the results from
	 * strlen() and similar functions respect the utf8 characters, causing binary data
	 * to return incorrect lengths.
	 *
	 * This function overrides the mbstring encoding to a binary-safe encoding, and
	 * resets it to the users expected encoding afterwards through the
	 * `reset_mbstring_encoding` function.
	 *
	 * It is safe to recursively call this function, however each
	 * `_mbstring_binary_safe_encoding()` call must be followed up with an equal number
	 * of `_reset_mbstring_encoding()` calls.
	 *
	 * @see Model_Crypto::_reset_mbstring_encoding
	 *
	 * @staticvar array $encodings
	 * @staticvar bool  $overloaded
	 *
	 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
	 *                    Default false.
	 */
	protected static function _mbstring_binary_safe_encoding($reset = false) {
		static $encodings = array();
		static $overloaded = null;
		
		if (is_null($overloaded)) {
			// phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
			$overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
		}
		
		if (false === $overloaded) { return; }
		
		if (!$reset) {
			$encoding = mb_internal_encoding();
			array_push($encodings, $encoding);
			mb_internal_encoding('ISO-8859-1');
		}
		
		if ($reset && $encodings) {
			$encoding = array_pop($encodings);
			mb_internal_encoding($encoding);
		}
	}
	
	/**
	 * Reset the mbstring internal encoding to a users previously set encoding.
	 *
	 * @see Model_Crypto::_mbstring_binary_safe_encoding
	 */
	protected static function _reset_mbstring_encoding() {
		self::_mbstring_binary_safe_encoding(true);
	}
	
	/**
	 * @param callable $function
	 * @param array $args
	 * @return mixed
	 */
	protected static function _call_mb_string_function($function, $args) {
		self::_mbstring_binary_safe_encoding();
		$return = call_user_func_array($function, $args);
		self::_reset_mbstring_encoding();
		return $return;
	}
	
	/**
	 * Multibyte safe strlen.
	 *
	 * @param $binary
	 * @return int
	 */
	public static function strlen($binary) {
		$args = func_get_args();
		return self::_call_mb_string_function('strlen', $args);
	}
	
	/**
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	public static function stripos($haystack, $needle, $offset = 0) {
		$args = func_get_args();
		return self::_call_mb_string_function('stripos', $args);
	}
	
	/**
	 * @param $string
	 * @return mixed
	 */
	public static function strtolower($string) {
		$args = func_get_args();
		return self::_call_mb_string_function('strtolower', $args);
	}
	
	/**
	 * @param $string
	 * @param $start
	 * @param $length
	 * @return mixed
	 */
	public static function substr($string, $start, $length = null) {
		if ($length === null) { $length = self::strlen($string); }
		return self::_call_mb_string_function('substr', array(
			$string, $start, $length
		));
	}
	
	/**
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return mixed
	 */
	public static function strpos($haystack, $needle, $offset = 0) {
		$args = func_get_args();
		return self::_call_mb_string_function('strpos', $args);
	}
	
	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @param int $length
	 * @return mixed
	 */
	public static function substr_count($haystack, $needle, $offset = 0, $length = null) {
		if ($length === null) { $length = self::strlen($haystack); }
		return self::_call_mb_string_function('substr_count', array(
			$haystack, $needle, $offset, $length
		));
	}
	
	/**
	 * @param $string
	 * @return mixed
	 */
	public static function strtoupper($string) {
		$args = func_get_args();
		return self::_call_mb_string_function('strtoupper', $args);
	}
	
	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return mixed
	 */
	public static function strrpos($haystack, $needle, $offset = 0) {
		$args = func_get_args();
		return self::_call_mb_string_function('strrpos', $args);
	}
}