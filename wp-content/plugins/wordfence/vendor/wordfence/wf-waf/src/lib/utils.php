<?php
if (defined('WFWAF_VERSION') && !defined('WFWAF_RUN_COMPLETE')) {

class wfWAFUtils {

	/**
	 * Return dot or colon notation of IPv4 or IPv6 address.
	 *
	 * @param string $ip
	 * @return string|bool
	 */
	public static function inet_ntop($ip) {
		// trim this to the IPv4 equiv if it's in the mapped range
		if (wfWAFUtils::strlen($ip) == 16 && wfWAFUtils::substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
			$ip = wfWAFUtils::substr($ip, 12, 4);
		}
		return self::hasIPv6Support() ? inet_ntop($ip) : self::_inet_ntop($ip);
	}

	/**
	 * Return the packed binary string of an IPv4 or IPv6 address.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function inet_pton($ip) {
		$default = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\x00\x00";
		try {
			// convert the 4 char IPv4 to IPv6 mapped version.
			$pton = str_pad(self::hasIPv6Support() ? inet_pton($ip) : self::_inet_pton($ip), 16, $default, STR_PAD_LEFT);
		}
		catch (Throwable $t) {
			$pton = $default;
		}
		return $pton;
	}

	/**
	 * Added compatibility for hosts that do not have inet_pton.
	 *
	 * @param $ip
	 * @return bool|string
	 */
	public static function _inet_pton($ip) {
		// IPv4
		if (preg_match('/^(?:\d{1,3}(?:\.|$)){4}/', $ip)) {
			$octets = explode('.', $ip);
			$bin = chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
			return $bin;
		}

		// IPv6
		if (preg_match('/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $ip)) {
			if ($ip === '::') {
				return "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			}
			$colon_count = wfWAFUtils::substr_count($ip, ':');
			$dbl_colon_pos = wfWAFUtils::strpos($ip, '::');
			if ($dbl_colon_pos !== false) {
				$ip = str_replace('::', str_repeat(':0000',
						(($dbl_colon_pos === 0 || $dbl_colon_pos === wfWAFUtils::strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
				$ip = trim($ip, ':');
			}

			$ip_groups = explode(':', $ip);
			$ipv6_bin = '';
			foreach ($ip_groups as $ip_group) {
				$ipv6_bin .= pack('H*', str_pad($ip_group, 4, '0', STR_PAD_LEFT));
			}

			return wfWAFUtils::strlen($ipv6_bin) === 16 ? $ipv6_bin : false;
		}

		// IPv4 mapped IPv6
		if (preg_match('/^((?:0{1,4}(?::|)){0,5})(::)?ffff:((?:\d{1,3}(?:\.|$)){4})$/i', $ip, $matches)) {
			$octets = explode('.', $matches[3]);
			return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
		}

		return false;
	}

	/**
	 * Added compatibility for hosts that do not have inet_ntop.
	 *
	 * @param $ip
	 * @return bool|string
	 */
	public static function _inet_ntop($ip) {
		// IPv4
		if (wfWAFUtils::strlen($ip) === 4) {
			return ord($ip[0]) . '.' . ord($ip[1]) . '.' . ord($ip[2]) . '.' . ord($ip[3]);
		}

		// IPv6
		if (wfWAFUtils::strlen($ip) === 16) {

			// IPv4 mapped IPv6
			if (wfWAFUtils::substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
				return "::ffff:" . ord($ip[12]) . '.' . ord($ip[13]) . '.' . ord($ip[14]) . '.' . ord($ip[15]);
			}

			$hex = bin2hex($ip);
			$groups = str_split($hex, 4);
			$collapse = false;
			$done_collapse = false;
			foreach ($groups as $index => $group) {
				if ($group == '0000' && !$done_collapse) {
					if (!$collapse) {
						$groups[$index] = ':';
					} else {
						$groups[$index] = '';
					}
					$collapse = true;
				} else if ($collapse) {
					$done_collapse = true;
					$collapse = false;
				}
				$groups[$index] = ltrim($groups[$index], '0');
			}
			$ip = join(':', array_filter($groups));
			$ip = str_replace(':::', '::', $ip);
			return $ip == ':' ? '::' : $ip;
		}

		return false;
	}

	/**
	 * Verify PHP was compiled with IPv6 support.
	 *
	 * Some hosts appear to not have inet_ntop, and others appear to have inet_ntop but are unable to process IPv6 addresses.
	 *
	 * @return bool
	 */
	public static function hasIPv6Support() {
		static $hasSupport = null;
		if ($hasSupport === null) {
			$hasSupport = defined('AF_INET6') && is_callable('inet_ntop') && is_callable('inet_pton');
		}
		return $hasSupport;
	}

	/**
	 * Expand a compressed printable representation of an IPv6 address.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function expandIPv6Address($ip) {
		$hex = bin2hex(self::inet_pton($ip));
		$ip = wfWAFUtils::substr(preg_replace("/([a-f0-9]{4})/i", "$1:", $hex), 0, -1);
		return $ip;
	}

	protected static $servicesJSON;

	public static function json_encode($string) {
		if (function_exists('json_encode')) {
			return json_encode($string);
		} else {
			if (!self::$servicesJSON) {
				require_once WFWAF_LIB_PATH . 'json.php';
				self::$servicesJSON = new wfServices_JSON();
			}
			return self::$servicesJSON->encodeUnsafe($string);
		}
	}

	public static function json_decode($string, $assoc_array = false) {
		if (function_exists('json_decode')) {
			return json_decode($string, $assoc_array);
		} else {
			if (!self::$servicesJSON) {
				require_once WFWAF_LIB_PATH . 'json.php';
				self::$servicesJSON = new wfServices_JSON();
			}
			$res = self::$servicesJSON->decode($string);
			if ($assoc_array)
				$res = self::_json_decode_object_helper($res);
			return $res;

		}
	}

	/**
	 * @param object $data
	 * @return array
	 */
	protected static function _json_decode_object_helper($data) {
		if (is_object($data))
			$data = get_object_vars($data);
		return is_array($data) ? array_map('wfWAFUtils::_json_decode_object_helper', $data) : $data;
	}

	public static function json_encode_limited($data, $limit, $truncatable) {
		$json = self::json_encode($data);
		$size = strlen($json);
		if ($size > $limit) {
			$json = null;
			$minimalData = $data;
			foreach ($minimalData as $key => &$value) {
				if (in_array($key, $truncatable)) {
					$value = '';
				}
			}
			$minimumSize = strlen(self::json_encode($minimalData));
			if ($minimumSize <= $limit) {
				$excess = $size - $limit;
				foreach ($truncatable as $field) {
					if (!array_key_exists($field, $data))
						continue;
					$value = $data[$field];
					if (is_string($value)) {
						$originalLength = strlen($value);
						$truncatedLength = max(0, $originalLength - $excess);
						$excess -= ($originalLength - $truncatedLength);
						$data[$field] = substr($value, 0, $truncatedLength);
					}
					if ($excess === 0) {
						$json = self::json_encode($data);
						break;
					}
				}
			}
		}
		return $json;
	}

	/**
	 * Compare two strings in constant time. It can leak the length of a string.
	 *
	 * @param string $a Expected string.
	 * @param string $b Actual string.
	 * @return bool Whether strings are equal.
	 */
	public static function hash_equals($a, $b) {
		$a_length = wfWAFUtils::strlen($a);
		if ($a_length !== wfWAFUtils::strlen($b)) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ($i = 0; $i < $a_length; $i++) {
			$result |= ord($a[$i]) ^ ord($b[$i]);
		}

		return $result === 0;
	}

	/**
	 * @param $algo
	 * @param $data
	 * @param $key
	 * @param bool|false $raw_output
	 * @return bool|string
	 */
	public static function hash_hmac($algo, $data, $key, $raw_output = false) {
		if (function_exists('hash_hmac')) {
			return hash_hmac($algo, $data, $key, $raw_output);
		}
		return self::_hash_hmac($algo, $data, $key, $raw_output);
	}

	/**
	 * @param $algo
	 * @param $data
	 * @param $key
	 * @param bool|false $raw_output
	 * @return bool|string
	 */
	private static function _hash_hmac($algo, $data, $key, $raw_output = false) {
		$packs = array('md5' => 'H32', 'sha1' => 'H40');

		if (!isset($packs[$algo]))
			return false;

		$pack = $packs[$algo];

		if (wfWAFUtils::strlen($key) > 64)
			$key = pack($pack, $algo($key));

		$key = str_pad($key, 64, chr(0));

		$ipad = (wfWAFUtils::substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
		$opad = (wfWAFUtils::substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

		$hmac = $algo($opad . pack($pack, $algo($ipad . $data)));

		if ($raw_output)
			return pack($pack, $hmac);
		return $hmac;
	}

	/**
	 * @param int $length
	 * @param string $chars
	 * @return string
	 */
	public static function getRandomString($length = 16, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|') {
		// This is faster than calling self::random_int for $length
		$bytes = self::random_bytes($length);
		$return = '';
		$maxIndex = wfWAFUtils::strlen($chars) - 1;
		for ($i = 0; $i < $length; $i++) {
			$fp = (float) ord($bytes[$i]) / 255.0; // convert to [0,1]
			$index = (int) (round($fp * $maxIndex));
			$return .= $chars[$index];
		}
		return $return;
	}

	/**
	 * Polyfill for random_bytes.
	 *
	 * @param int $bytes
	 * @return string
	 */
	public static function random_bytes($bytes) {
		$bytes = (int) $bytes;
		if (function_exists('random_bytes')) {
			try {
				$rand = random_bytes($bytes);
				if (is_string($rand) && wfWAFUtils::strlen($rand) === $bytes) {
					return $rand;
				}
			} catch (Exception $e) {
				// Fall through
			} catch (TypeError $e) {
				// Fall through
			} catch (Error $e) {
				// Fall through
			}
		}
		if (function_exists('mcrypt_create_iv')) {
			// phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.mcrypt_create_ivDeprecatedRemoved,PHPCompatibility.Extensions.RemovedExtensions.mcryptDeprecatedRemoved,PHPCompatibility.Constants.RemovedConstants.mcrypt_dev_urandomDeprecatedRemoved
			$rand = @mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
			if (is_string($rand) && wfWAFUtils::strlen($rand) === $bytes) {
				return $rand;
			}
		}
		if (function_exists('openssl_random_pseudo_bytes')) {
			$rand = @openssl_random_pseudo_bytes($bytes, $strong);
			if (is_string($rand) && wfWAFUtils::strlen($rand) === $bytes) {
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
			} catch (Exception $e) {
				// Fall through
			} catch (TypeError $e) {
				// Fall through
			} catch (Error $e) {
				// Fall through
			}
		}
		$diff = $max - $min;
		$bytes = self::random_bytes(4);
		if ($bytes === false || wfWAFUtils::strlen($bytes) != 4) {
			throw new RuntimeException("Unable to get 4 bytes");
		}
		$val = @unpack("Nint", $bytes);
		$val = $val['int'] & 0x7FFFFFFF;
		$fp = (float) $val / 2147483647.0; // convert to [0,1]
		return (int) (round($fp * $diff) + $min);
	}

	/**
	 * @param mixed $subject
	 * @return array|string
	 */
	public static function stripMagicQuotes($subject) {
		// phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.magic_quotes_sybaseDeprecatedRemoved
		$sybase = ini_get('magic_quotes_sybase');
		$sybaseEnabled = ((is_numeric($sybase) && $sybase) ||
			(is_string($sybase) && $sybase && !in_array(wfWAFUtils::strtolower($sybase), array(
					'off',
					'false'
				))));
		if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70400) { //Avoid get_magic_quotes_gpc on PHP >= 7.4.0
			return $subject;
		}
		// phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.get_magic_quotes_gpcDeprecated
		if ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || $sybaseEnabled) {
			return self::stripslashes_deep($subject);
		}
		return $subject;
	}

	/**
	 * @param mixed $subject
	 * @return array|string
	 */
	public static function stripslashes_deep($subject) {
		if (is_array($subject)) {
			return array_map(array(
				'self', 'stripslashes_deep',
			), $subject);
		} else if (is_string($subject)) {
			return stripslashes($subject);
		}
		return $subject;
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
	 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
	 * of `reset_mbstring_encoding()` calls.
	 *
	 * @see wfWAFUtils::reset_mbstring_encoding
	 *
	 * @staticvar array $encodings
	 * @staticvar bool  $overloaded
	 *
	 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
	 *                    Default false.
	 */
	public static function mbstring_binary_safe_encoding($reset = false) {
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
	 * @see wfWAFUtils::mbstring_binary_safe_encoding
	 */
	public static function reset_mbstring_encoding() {
		self::mbstring_binary_safe_encoding(true);
	}

	/**
	 * @param callable $function
	 * @param array $args
	 * @return mixed
	 */
	protected static function callMBSafeStrFunction($function, $args) {
		self::mbstring_binary_safe_encoding();
		$return = call_user_func_array($function, $args);
		self::reset_mbstring_encoding();
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
		return self::callMBSafeStrFunction('strlen', $args);
	}

	/**
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	public static function stripos($haystack, $needle, $offset = 0) {
		$args = func_get_args();
		return self::callMBSafeStrFunction('stripos', $args);
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public static function strtolower($string) {
		$args = func_get_args();
		return self::callMBSafeStrFunction('strtolower', $args);
	}

	/**
	 * @param $string
	 * @param $start
	 * @param $length
	 * @return mixed
	 */
	public static function substr($string, $start, $length = null) {
		if ($length === null) { $length = self::strlen($string); }
		return self::callMBSafeStrFunction('substr', array(
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
		return self::callMBSafeStrFunction('strpos', array(
			(string)$haystack,
			(string)$needle,
			(int)$offset,
		));
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
		return self::callMBSafeStrFunction('substr_count', array(
			(string)$haystack, (string)$needle, (int)$offset, $length
		));
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public static function strtoupper($string) {
		$args = func_get_args();
		return self::callMBSafeStrFunction('strtoupper', $args);
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return mixed
	 */
	public static function strrpos($haystack, $needle, $offset = 0) {
		$args = func_get_args();
		return self::callMBSafeStrFunction('strrpos', $args);
	}

	/**
	 * @param string $val An ini byte size value (e.g., 20M)
	 * @return int
	 */
	public static function iniSizeToBytes($val) {
		$val = trim($val);
		if (preg_match('/^\d+$/', $val)) {
			return (int) $val;
		}

		$last = strtolower(substr($val, -1));
		$val = (int) substr($val, 0, -1);
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	public static function reverseLookup($IP) {
		$IPn = self::inet_pton($IP);
		// This function works for IPv4 or IPv6
		$host = null;
		if (function_exists('gethostbyaddr') && is_callable('gethostbyaddr')) {
			$host = @gethostbyaddr($IP);
		}
		if (!$host) {
			$ptr = false;
			if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
				$ptr = implode(".", array_reverse(explode(".", $IP))) . ".in-addr.arpa";
			} else if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
				$ptr = implode(".", array_reverse(str_split(bin2hex($IPn)))) . ".ip6.arpa";
			}

			if ($ptr && function_exists('dns_get_record') && is_callable('dns_get_record')) {
				$host = @dns_get_record($ptr, DNS_PTR);
				if ($host && is_array($host) && count($host)) {
					$host = $host[0]['target'];
				}
			}
		}
		if (!$host) {
			return '';
		}
		return $host;
	}

	public static function patternToRegex($pattern, $mod = 'i', $sep = '/') {
		$pattern = preg_quote(trim($pattern), $sep);
		$pattern = str_replace(' ', '\s', $pattern);
		return $sep . '^' . str_replace('\*', '.*', $pattern) . '$' . $sep . $mod;
	}

	public static function isUABlocked($uaPattern, $ua) { // takes a pattern using asterisks as wildcards, turns it into regex and checks it against the visitor UA returning true if blocked
		return fnmatch($uaPattern, $ua, FNM_CASEFOLD);
	}

	public static function isRefererBlocked($refPattern, $referer) {
		return fnmatch($refPattern, $referer, FNM_CASEFOLD);
	}

	public static function extractBareURI($URL) {
		$URL = (string)$URL;
		$URL = preg_replace('/^https?:\/\/[^\/]+/i', '', $URL); //strip of method and host
		$URL = preg_replace('/\#.*$/', '', $URL); //strip off fragment
		$URL = preg_replace('/\?.*$/', '', $URL); //strip off query string
		return $URL;
	}

	public static function extractHostname($str) {
		if (preg_match('/https?:\/\/([a-zA-Z0-9\.\-]+)(?:\/|$)/i', $str, $matches)) {
			return strtolower($matches[1]);
		}
		else {
			return false;
		}
	}

	public static function redirect($location, $status = 302) {
		$is_apache = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false);
		$is_IIS = !$is_apache && (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false);

		self::doNotCache();

		if (!$is_IIS && PHP_SAPI != 'cgi-fcgi') {
			self::statusHeader($status); // This causes problems on IIS and some FastCGI setups
		}

		header("Location: {$location}", true, $status);
		exit;
	}

	public static function statusHeader($code) {
		$code = abs(intval($code));

		$statusCodes = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			308 => 'Permanent Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			421 => 'Misdirected Request',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',
			451 => 'Unavailable For Legal Reasons',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended',
			511 => 'Network Authentication Required',
		);

		$description = (isset($statusCodes[$code]) ? $statusCodes[$code] : '');

		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if (!in_array($protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'))) {
			$protocol = 'HTTP/1.0';
		}

		$header = "{$protocol} {$code} {$description}";
		@header($header, true, $code);
	}

	public static function doNotCache() {
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must-revalidate, private, max-age=0");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); //In the past
		if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		if (!defined('DONOTCDN')) { define('DONOTCDN', true); }
		if (!defined('DONOTCACHEOBJECT')) { define('DONOTCACHEOBJECT', true); }
	}
	
	/**
	 * Check if an IP address is in a network block
	 *
	 * @param string	$subnet	Single IP or subnet in CIDR notation (e.g. '192.168.100.0' or '192.168.100.0/22')
	 * @param string	$ip		IPv4 or IPv6 address in dot or colon notation
	 * @return boolean
	 */
	public static function subnetContainsIP($subnet, $ip) {
		static $_network_cache = array();
		static $_ip_cache = array();
		static $_masks = array(
			0 => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			1 => "\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			2 => "\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			3 => "\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			4 => "\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			5 => "\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			6 => "\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			7 => "\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			8 => "\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			9 => "\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			10 => "\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			11 => "\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			12 => "\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			13 => "\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			14 => "\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			15 => "\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			16 => "\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			17 => "\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			18 => "\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			19 => "\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			20 => "\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			21 => "\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			22 => "\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			23 => "\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			24 => "\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			25 => "\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			26 => "\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			27 => "\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			28 => "\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			29 => "\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			30 => "\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			31 => "\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			32 => "\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			33 => "\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			34 => "\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			35 => "\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			36 => "\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			37 => "\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			38 => "\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			39 => "\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			40 => "\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			41 => "\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			42 => "\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			43 => "\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			44 => "\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			45 => "\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			46 => "\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			47 => "\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			48 => "\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			49 => "\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			50 => "\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			51 => "\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			52 => "\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			53 => "\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			54 => "\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			55 => "\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			56 => "\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00",
			57 => "\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00\x00",
			58 => "\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00\x00",
			59 => "\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00\x00",
			60 => "\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00\x00",
			61 => "\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00\x00",
			62 => "\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00\x00",
			63 => "\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00\x00",
			64 => "\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00",
			65 => "\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00\x00",
			66 => "\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00\x00",
			67 => "\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00\x00",
			68 => "\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00\x00",
			69 => "\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00\x00",
			70 => "\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00\x00",
			71 => "\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00\x00",
			72 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00",
			73 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00\x00",
			74 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00\x00",
			75 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00\x00",
			76 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00\x00",
			77 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00\x00",
			78 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00\x00",
			79 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00\x00",
			80 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00",
			81 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00\x00",
			82 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00\x00",
			83 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00\x00",
			84 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00\x00",
			85 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00\x00",
			86 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00\x00",
			87 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00\x00",
			88 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00",
			89 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00\x00",
			90 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00\x00",
			91 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00\x00",
			92 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00\x00",
			93 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00\x00",
			94 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00\x00",
			95 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00\x00",
			96 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00",
			97 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00\x00",
			98 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00\x00",
			99 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00\x00",
			100 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00\x00",
			101 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00\x00",
			102 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00\x00",
			103 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00\x00",
			104 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00\x00",
			105 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00\x00",
			106 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00\x00",
			107 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00\x00",
			108 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00\x00",
			109 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00\x00",
			110 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00\x00",
			111 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00\x00",
			112 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00\x00",
			113 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80\x00",
			114 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0\x00",
			115 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0\x00",
			116 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0\x00",
			117 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8\x00",
			118 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc\x00",
			119 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe\x00",
			120 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x00",
			121 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x80",
			122 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xc0",
			123 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xe0",
			124 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf0",
			125 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xf8",
			126 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfc",
			127 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xfe",
			128 => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
		);
		/*
		 * The above is generated by:
		 * 
		   function gen_mask($prefix, $size = 128) {
				//Workaround to avoid overflow, split into four pieces			
				$mask_1 = (pow(2, $size / 4) - 1) ^ (pow(2, min($size / 4, max(0, 1 * $size / 4 - $prefix))) - 1);
				$mask_2 = (pow(2, $size / 4) - 1) ^ (pow(2, min($size / 4, max(0, 2 * $size / 4 - $prefix))) - 1);
				$mask_3 = (pow(2, $size / 4) - 1) ^ (pow(2, min($size / 4, max(0, 3 * $size / 4 - $prefix))) - 1);
				$mask_4 = (pow(2, $size / 4) - 1) ^ (pow(2, min($size / 4, max(0, 4 * $size / 4 - $prefix))) - 1);
				return ($mask_1 ? pack('N', $mask_1) : "\0\0\0\0") . ($mask_2 ? pack('N', $mask_2) : "\0\0\0\0") . ($mask_3 ? pack('N', $mask_3) : "\0\0\0\0") . ($mask_4 ? pack('N', $mask_4) : "\0\0\0\0");
			}
			
			$masks = array();
			for ($i = 0; $i <= 128; $i++) {
				$mask = gen_mask($i);
				$chars = str_split($mask);
				$masks[] = implode('', array_map(function($c) { return '\\x' . bin2hex($c); }, $chars));
			}
			
			echo 'array(' . "\n";
			foreach ($masks as $index => $m) {
				echo "\t{$index} => \"{$m}\",\n";
			}
			echo ')';
		 *
		 */
		
		if (isset($_network_cache[$subnet])) {
			list($bin_network, $prefix, $masked_network) = $_network_cache[$subnet];
			$mask = $_masks[$prefix];
		}
		else {
			list($network, $prefix) = array_pad(explode('/', $subnet, 2), 2, null);
			if (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				// If no prefix was supplied, 32 is implied for IPv4
				if ($prefix === null) {
					$prefix = 32;
				}
				
				// Validate the IPv4 network prefix
				if ($prefix < 0 || $prefix > 32) {
					return false;
				}
				
				// Increase the IPv4 network prefix to work in the IPv6 address space
				$prefix += 96;
			}
			else {
				// If no prefix was supplied, 128 is implied for IPv6
				if ($prefix === null) {
					$prefix = 128;
				}
				
				// Validate the IPv6 network prefix
				if ($prefix < 1 || $prefix > 128) {
					return false;
				}
			}
			$mask = $_masks[$prefix];
			$bin_network = self::inet_pton($network);
			$masked_network = $bin_network & $mask;
			$_network_cache[$subnet] = array($bin_network, $prefix, $masked_network);
		}
		
		if (isset($_ip_cache[$ip]) && isset($_ip_cache[$ip][$prefix])) {
			list($bin_ip, $masked_ip) = $_ip_cache[$ip][$prefix];
		}
		else {
			$bin_ip = self::inet_pton($ip);
			$masked_ip = $bin_ip & $mask;
			if (!isset($_ip_cache[$ip])) {
				$_ip_cache[$ip] = array();
			}
			$_ip_cache[$ip][$prefix] = array($bin_ip, $masked_ip);
		}
		
		return ($masked_ip === $masked_network);
	}

	/**
	 * Behaves exactly like PHP's parse_url but uses WP's compatibility fixes for early PHP 5 versions.
	 *
	 * @param string $url
	 * @param int $component
	 * @return mixed
	 */
	public static function parse_url($url, $component = -1) {
		$to_unset = array();
		$url = strval($url);

		if (substr($url, 0, 2) === '//') {
			$to_unset[] = 'scheme';
			$url = 'placeholder:' . $url;
		}
		elseif (substr($url, 0, 1) === '/') {
			$to_unset[] = 'scheme';
			$to_unset[] = 'host';
			$url = 'placeholder://placeholder' . $url;
		}

		$parts = @parse_url($url);

		if ($parts === false || !is_array($parts)) { // Parsing failure
			return $parts;
		}

		// Remove the placeholder values
		foreach ($to_unset as $key) {
			unset($parts[$key]);
		}

		if ($component === -1) {
			return $parts;
		}

		$translation = array(
			PHP_URL_SCHEME   => 'scheme',
			PHP_URL_HOST     => 'host',
			PHP_URL_PORT     => 'port',
			PHP_URL_USER     => 'user',
			PHP_URL_PASS     => 'pass',
			PHP_URL_PATH     => 'path',
			PHP_URL_QUERY    => 'query',
			PHP_URL_FRAGMENT => 'fragment',
		);

		$key = false;
		if (isset($translation[$component])) {
			$key = $translation[$component];
		}

		if ($key !== false && is_array($parts) && isset($parts[$key])) {
			return $parts[$key];
		}

		return null;
	}

	/**
	 * Validates the URL, supporting both scheme-relative and path-relative formats.
	 *
	 * @param $url
	 * @return mixed
	 */
	public static function validate_url($url) {
		$url = strval($url);

		if (substr($url, 0, 2) === '//') {
			$url = 'placeholder:' . $url;
		}
		elseif (substr($url, 0, 1) === '/') {
			$url = 'placeholder://placeholder' . $url;
		}

		return filter_var($url, FILTER_VALIDATE_URL);
	}

	public static function rawPOSTBody() {
		// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		global $HTTP_RAW_POST_DATA;
		// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		if (empty($HTTP_RAW_POST_DATA)) { //Defined if always_populate_raw_post_data is on, PHP < 7, and the encoding type is not multipart/form-data
			$avoidPHPInput = false;
			try {
				$avoidPHPInput = wfWAF::getSharedStorageEngine() && wfWAF::getSharedStorageEngine()->getConfig('avoid_php_input', false);
			}
			catch (Exception $e) {
				//Ignore
			}
			
			if ($avoidPHPInput) { //Some custom PHP builds break reading from php://input
				//Reconstruct the best possible approximation of it from $_POST if populated -- won't help JSON or other raw payloads
				$data = http_build_query($_POST, '', '&');
			}
			else {
				$data = file_get_contents('php://input'); //Available if the encoding type is not multipart/form-data; it can only be read once prior to PHP 5.6 so we save it in $HTTP_RAW_POST_DATA for WP Core and others
				
				//For our purposes, we don't currently need the raw POST body if it's multipart/form-data since the data will be in $_POST/$_FILES. If we did, we could reconstruct the body here.
				
				// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
				$HTTP_RAW_POST_DATA = $data;
			}
		}
		else {
			// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
			$data =& $HTTP_RAW_POST_DATA;
		}
		return $data;
	}

	/**
	 * Returns the current timestamp, adjusted as needed to get close to what we consider a true timestamp. We use this
	 * because a significant number of servers are using a drastically incorrect time.
	 *
	 * @return int
	 */
	public static function normalizedTime() {
		$offset = 0;
		try {
			$offset = wfWAF::getInstance()->getStorageEngine()->getConfig('timeoffset_ntp', false, 'synced');
			if ($offset === false) {
				$offset = wfWAF::getInstance()->getStorageEngine()->getConfig('timeoffset_wf', false, 'synced');
			}
		}
		catch (Exception $e) {
			//Ignore
		}
		return time() + ((int) $offset);
	}

	/**
	 * @param $file
	 * @return array|bool
	 */
	public static function extractCredentialsWPConfig($file, &$return = array()) {
		$configContents = file_get_contents($file);
		$tokens = token_get_all($configContents);
		$tokens = array_values(array_filter($tokens, 'wfWAFUtils::_removeUnneededTokens'));

		$parsedConstants = array();
		$parsedVariables = array();
		for ($i = 0; $i < count($tokens); $i++) {
			$token = $tokens[$i];
			if (is_array($token)) {
				if (token_name($token[0]) === 'T_STRING' && strtolower($token[1]) === 'define') {
					$startParenToken = $tokens[$i + 1];
					$constantNameToken = $tokens[$i + 2];
					$commaToken = $tokens[$i + 3];
					$constantValueToken = $tokens[$i + 4];
					$endParenToken = $tokens[$i + 5];
					if (
						!is_array($startParenToken) && $startParenToken === '(' &&
						is_array($constantNameToken) && token_name($constantNameToken[0]) === 'T_CONSTANT_ENCAPSED_STRING' &&
						!is_array($commaToken) && $commaToken === ',' &&
						is_array($constantValueToken) && in_array(token_name($constantValueToken[0]), array('T_CONSTANT_ENCAPSED_STRING', 'T_STRING')) &&
						!is_array($endParenToken) && $endParenToken === ')'
					) {
						if (token_name($constantValueToken[0]) === 'T_STRING') {
							$value = defined($constantValueToken[1]) ? constant($constantValueToken[1]) : null;
						}
						else {
							$value = self::substr($constantValueToken[1], 1, -1);
						}
						$parsedConstants[self::substr($constantNameToken[1], 1, -1)] = $value;
					}
				}
				if (token_name($token[0]) === 'T_VARIABLE') {
					$assignmentToken = $tokens[$i + 1];
					$variableValueToken = $tokens[$i + 2];
					if (
						!is_array($assignmentToken) && $assignmentToken === '=' &&
						is_array($variableValueToken) && token_name($variableValueToken[0]) === 'T_CONSTANT_ENCAPSED_STRING'
					) {
						$parsedVariables[$token[1]] = self::substr($variableValueToken[1], 1, -1);
					}
				}
			}
		}

		$optionalConstants = array(
			'flags' => 'MYSQL_CLIENT_FLAGS'
		);
		$constants = array(
			'user'      => 'DB_USER',
			'pass'      => 'DB_PASSWORD',
			'database'  => 'DB_NAME',
			'host'      => 'DB_HOST',
			'charset'   => array('constant' => 'DB_CHARSET', 'default' => ''),
			'collation' => array('constant' => 'DB_COLLATE', 'default' => ''),
		);
		$constants += $optionalConstants;
		foreach ($constants as $key => $constant) {
			unset($defaultValue);
			if (is_array($constant)) {
				$defaultValue = $constant['default'];
				$constant = $constant['constant'];
			}
			
			if (array_key_exists($key, $return)) {
				continue;
			}
			else if (array_key_exists($constant, $parsedConstants)) {
				$return[$key] = $parsedConstants[$constant];
			}
			else if (!array_key_exists($key, $optionalConstants)){
				if (isset($defaultValue)) {
					$return[$key] = $defaultValue;
				}
				else {
					return ($return = false);
				}
			}
		}

		/**
		 * @see \wpdb::parse_db_host
		 */
		$socketPos = self::strpos($return['host'], ':/');
		if ($socketPos !== false) {
			$return['socket'] = self::substr($return['host'], $socketPos + 1);
			$return['host'] = self::substr($return['host'], 0, $socketPos);
		}

		if ( self::substr_count( $return['host'], ':' ) > 1 ) {
			$pattern = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
			$return['ipv6'] = true;
		} else {
			$pattern = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
		}

		$matches = array();
		$result = preg_match($pattern, $return['host'], $matches);

		if (1 !== $result) {
			return ($return = false);
		}

		foreach (array('host', 'port') as $component) {
			if (!empty($matches[$component])) {
				$return[$component] = $matches[$component];
			}
		}

		if (!array_key_exists('tablePrefix', $return)) {
			if (array_key_exists('$table_prefix', $parsedVariables)) {
				$return['tablePrefix'] = $parsedVariables['$table_prefix'];
			} else {
				return ($return = false);
			}
		}
		return $return;
	}

	protected static function _removeUnneededTokens($token) {
		if (is_array($token)) {
			return !in_array(token_name($token[0]), array(
				'T_DOC_COMMENT', 'T_WHITESPACE'
			));
		}
		return true;
	}

	public static function isVersionBelow($target, $compared) {
		return $compared === null || version_compare($compared, $target, '<');
	}
	
	public static function isCli() {
		return (@php_sapi_name()==='cli') || !array_key_exists('REQUEST_METHOD', $_SERVER);
	}
}
}