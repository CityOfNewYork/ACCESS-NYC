<?php
require_once(dirname(__FILE__) . '/wfConfig.php');
class wfUtils {
	const DEFAULT_MAX_SERIALIZED_INPUT_LENGTH = 65536;
	const DEFAULT_MAX_SERIALIZED_ARRAY_LENGTH = 1024;
	const DEFAULT_MAX_SERIALIZED_ARRAY_DEPTH = 5;
	
	//Fields for wfUtils::parseCallable
	const CALLABLE_CLASS = 'class';
	const CALLABLE_FUNCTION = 'function';
	const CALLABLE_IS_INSTANCE = 'instance';
	const CALLABLE_IS_CLOSURE = 'closure';
	const CALLABLE_IS_INVOKABLE = 'invokable';
	
	//Flags for wfUtils::parse_version
	const VERSION_MAJOR = 'major';
	const VERSION_MINOR = 'minor';
	const VERSION_PATCH = 'patch';
	const VERSION_PRE_RELEASE = 'pre-release';
	const VERSION_BUILD = 'build';
	
	//Flags for array_diff_assoc
	const ARRAY_DIFF_ORDERED_ARRAYS = 1; //When specified, non-associative arrays are treated as if the ordering matters. The default is to ignore the ordering and only care about the content
	
	//Constants for wfUtils::serverIPs
	const SERVER_ADDR_CACHE_TTL =  86400;
	const SERVER_ADDR_REFRESH_TTL =  14400;
	
	private static $isWindows = false;
	public static $scanLockFH = false;
	private static $lastErrorReporting = false;
	private static $lastDisplayErrors = false;
	public static function patternToRegex($pattern, $mod = 'i', $sep = '/') {
		$pattern = preg_quote(trim($pattern), $sep);
		$pattern = str_replace(' ', '\s', $pattern);
		return $sep . '^' . str_replace('\*', '.*', $pattern) . '$' . $sep . $mod;
	}
	public static function versionedAsset($subpath) {
		$version = WORDFENCE_BUILD_NUMBER;
		if ($version != 'WORDFENCE_BUILD_NUMBER' && preg_match('/^(.+?)(\.[^\.]+)$/', $subpath, $matches)) {
			$prefix = $matches[1];
			$suffix = $matches[2];
			return $prefix . '.' . $version . $suffix;
		}
		
		return $subpath;
	}
	public static function makeTimeAgo($secs, $noSeconds = false) {
		if($secs < 1){
			return __("a moment", 'wordfence');
		}
		
		if (function_exists('date_diff')) {
			$now = new DateTime();
			$utc = new DateTimeZone('UTC');
			$dtStr = gmdate("c", (int) ($now->getTimestamp() + $secs)); //Have to do it this way because of PHP 5.2
			$then = new DateTime($dtStr, $utc);
			
			$diff = $then->diff($now);
			$years = $diff->y;
			$months = $diff->m;
			$days = $diff->d;
			$hours = $diff->h;
			$minutes = $diff->i;
		}
		else {
			$years = 0;
			$months = floor($secs / (86400 * 30));
			$days = floor($secs / 86400);
			$hours = floor($secs / 3600);
			$minutes = floor($secs / 60);
			
			if ($months) {
				$days -= $months * 30;
			}
			else if ($days) {
				$hours -= $days * 24;
			}
			else if ($hours) {
				$minutes -= $hours * 60;
			}
		}
		
		if ($years) {
			return $years . ' ' . _n('year', 'years', $years, 'wordfence') .
				(is_numeric($months) ? ' ' . $months . ' ' . _n('month', 'months', $months, 'wordfence') : '');
		}
		else if ($months) {
			return $months . ' ' . _n('month', 'months', $months, 'wordfence') .
				(is_numeric($days) ? ' ' . $days . ' ' . _n('day', 'days', $days, 'wordfence') : '');
		}
		else if ($days) {
			return $days . ' ' . _n('day', 'days', $days, 'wordfence') .
				(is_numeric($hours) ? ' ' . $hours . ' ' . _n('hour', 'hours', $hours, 'wordfence') : '');
		}
		else if ($hours) {
			return $hours . ' ' . _n('hour', 'hours', $hours, 'wordfence') .
				(is_numeric($minutes) ? ' ' . $minutes . ' ' . _n('minute', 'minutes', $minutes, 'wordfence') : '');
		}
		else if ($minutes) {
			return $minutes . ' ' . _n('minute', 'minutes', $minutes, 'wordfence');
		}
		else {
			if($noSeconds){
				return __("less than a minute", 'wordfence');
			} else {
				return sprintf(/* translators: Time duration (plural). */ __("%d seconds", 'wordfence'), floor($secs));
			}
		}
	}
	public static function makeDuration($secs, $createExact = false) {
		$components = array();
		
		$months = floor($secs / (86400 * 30)); $secs -= $months * 86400 * 30;
		$days = floor($secs / 86400); $secs -= $days * 86400;
		$hours = floor($secs / 3600); $secs -= $hours * 3600;
		$minutes = floor($secs / 60); $secs -= $minutes * 60;
		
		if ($months) {
			$components[] = $months . ' ' . _n('month', 'months', $months, 'wordfence');
			if (!$createExact) {
				$hours = $minutes = $secs = 0;
			}
		}
		if ($days) {
			$components[] = $days . ' ' . _n('day', 'days', $days, 'wordfence');
			if (!$createExact) {
				$minutes = $secs = 0;
			}
		}
		if ($hours) {
			$components[] = $hours . ' ' . _n('hour', 'hours', $hours, 'wordfence');
			if (!$createExact) {
				$secs = 0;
			}
		}
		if ($minutes) {
			$components[] = $minutes . ' ' . _n('minute', 'minutes', $minutes, 'wordfence');
		}
		if ($secs && $secs >= 1) {
			$components[] = $secs . ' ' . _n('second', 'seconds', $secs, 'wordfence');
		}
		
		if (empty($components)) {
			$components[] = __('less than 1 second', 'wordfence');
		}
		
		return implode(' ', $components);
	}
	public static function pluralize($m1, $m1Singular, $m1Plural, $m2 = false, $m2Singular = false, $m2Plural = false) {
		$m1Text = _n($m1Singular, $m1Plural, $m1, 'wordfence');
		if (is_numeric($m2)) {
			$m2Text = _n($m2Singular, $m2Plural, $m2, 'wordfence');
			return "$m1 $m1Text $m2 $m2Text";
		} else {
			return "$m1 $m1Text";
		}
	}
	public static function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

		return round($bytes, $precision) . ' ' . $units[$pow];
	}
	
	/**
	 * Returns the PHP version formatted for display, stripping off the build information when present.
	 * 
	 * @return string
	 */
	public static function cleanPHPVersion() {
		$version = phpversion();
		if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches)) {
			return $matches[1];
		}
		return $version;
	}
	
	/**
	 * Safe unserialize() replacement
	 * - accepts a strict subset of PHP's native serialized representation
	 * - does not unserialize objects
	 *
	 * @param string $str
	 * @return mixed
	 */
	public static function _safe_unserialize($str, $limit_input_length = self::DEFAULT_MAX_SERIALIZED_INPUT_LENGTH, $limit_array_length = self::DEFAULT_MAX_SERIALIZED_ARRAY_LENGTH, $limit_array_depth = self::DEFAULT_MAX_SERIALIZED_ARRAY_DEPTH) {
		if (empty($str) || !is_string($str)) { return false; }
		if (strlen($str) > $limit_input_length) { return false; }
		if (!is_serialized($str)) { return false; }
		
		$stack = array();
		$expected = array();
		
		/*
		 * states:
		 *   0 - initial state, expecting a single value or array
		 *   1 - terminal state
		 *   2 - in array, expecting end of array or a key
		 *   3 - in array, expecting value or another array
		 */
		$state = 0;
		while ($state != 1) {
			$type = isset($str[0]) ? $str[0] : '';
			if ($type == '}') {
				$str = substr($str, 1);
			} else if ($type == 'N' && $str[1] == ';') {
				$value = null;
				$str = substr($str, 2);
			} else if ($type == 'b' && preg_match('/^b:([01]);/', $str, $matches)) {
				$value = $matches[1] == '1' ? true : false;
				$str = substr($str, 4);
			} else if ($type == 'i' && preg_match('/^i:(-?[0-9]+);(.*)/s', $str, $matches)) {
				$value = (int) $matches[1];
				$str = $matches[2];
			} else if ($type == 'd' && preg_match('/^d:(-?[0-9]+\.?[0-9]*(E[+-][0-9]+)?);(.*)/s', $str, $matches)) {
				$value = (float) $matches[1];
				$str = $matches[3];
			} else if ($type == 's' && preg_match('/^s:([0-9]+):"(.*)/s', $str, $matches) && substr($matches[2], (int) $matches[1], 2) == '";') {
				$value = substr($matches[2], 0, (int) $matches[1]);
				$str = substr($matches[2], (int) $matches[1] + 2);
			} else if ($type == 'a' && preg_match('/^a:([0-9]+):{(.*)/s', $str, $matches) && $matches[1] < $limit_array_length) {
				$expectedLength = (int) $matches[1];
				$str = $matches[2];
			} else {
				// object or unknown/malformed type
				return false;
			}
			
			switch ($state) {
				case 3: // in array, expecting value or another array
					if ($type == 'a') {
						if (count($stack) >= $limit_array_depth) { return false; }
						
						$stack[] = &$list;
						$list[$key] = array(); //$key is set in state 2
						$list = &$list[$key];
						$expected[] = $expectedLength;
						$state = 2;
						break;
					}
					if ($type != '}') {
						$list[$key] = $value;
						$state = 2;
						break;
					}
					
					// missing array value
					return false;
				
				case 2: // in array, expecting end of array or a key
					if ($type == '}') {
						if (count($list) < end($expected)) {
							// array size less than expected
							return false;
						}
						
						unset($list);
						$list = &$stack[count($stack) - 1];
						array_pop($stack);
						
						// go to terminal state if we're at the end of the root array
						array_pop($expected);
						if (count($expected) == 0) {
							$state = 1;
						}
						break;
					}
					if ($type == 'i' || $type == 's') {
						if (count($list) >= $limit_array_length) { return false; }
						if (count($list) >= end($expected)) { return false; }
						
						$key = $value;
						$state = 3;
						break;
					}
					
					// illegal array index type
					return false;
				
				case 0: // expecting array or value
					if ($type == 'a') {
						if (count($stack) >= $limit_array_depth) { return false; }
						
						$data = array();
						$list = &$data;
						$expected[] = $expectedLength;
						$state = 2;
						break;
					}
					if ($type != '}') {
						$data = $value;
						$state = 1;
						break;
					}
					
					// not in array
					return false;
			}
		}
		
		if (!empty($str)) { return false; } // trailing data in input
		return $data;
	}
	
	/**
	 * Wrapper for _safe_unserialize() that handles multibyte encoding issues
	 *
	 * @param string $str
	 * @return mixed
	 */
	public static function safe_unserialize($str) {
		// ensure we use the byte count for strings even when strlen() is overloaded by mb_strlen()
		if (function_exists('mb_internal_encoding') && (((int) ini_get('mbstring.func_overload')) & 2)) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
			$mbIntEnc = mb_internal_encoding();
			mb_internal_encoding('ASCII');
		}
		
		$out = self::_safe_unserialize($str);
		
		if (isset($mbIntEnc)) {
			mb_internal_encoding($mbIntEnc);
		}
		return $out;
	}
	
	/**
	 * If $value is not null, this returns $value unchanged. Otherwise it returns one of two values:
	 * 1. If $orCallable is a valid callable, it returns the result
	 * 2. Otherwise it returns $default 
	 * 
	 * @param mixed $value
	 * @param mixed $default
	 * @param callable|null $orCallable
	 * @return mixed
	 */
	public static function ifnull($value, $default = '', $orCallable = null) {
		if (is_null($value)) {
			if (is_callable($orCallable)) {
				return $orCallable();
			}
			return $default;
		}
		return $value;
	}
	
	/**
	 * Returns a diff on the passed arrays. The behavior varies based on the content of the arrays themselves and any
	 * flags passed. The resulting structure will be some variant of:
	 * 
	 * ['added' => [...], 'removed' => [...]]
	 * 
	 * 1. If both $a and $b are non-associative arrays, the result will not include keys in `added` and `removed`.
	 * 2. If either or both of $a and $b are associative arrays, the result will include keys that are also factored
	 *    into the comparison.
	 * 
	 * @param array $a
	 * @param array $b
	 * @param int $flags
	 * @return array
	 */
	public static function array_diff($a, $b, $flags = 0) {
		$result = array();
		if (!self::is_assoc($a) && !self::is_assoc($b)) {
			$result['added'] = array_diff($b, $a);
			$result['removed'] = array_diff($a, $b);
		}
		else {
			$result['added'] = self::array_diff_assoc($b, $a);
			$result['removed'] = self::array_diff_assoc($a, $b);
		}
		return $result;
	}
	
	/**
	 * Improved version of array_diff_assoc that handles multidimensional arrays. The resulting array will contain all 
	 * key/values from $a that are not present in $b.
	 * 
	 * For nested arrays, the behavior for inequality is this:
	 * 	- If $a[key] contains values $b[key] does not, an array of those missing values is set for `key` in the result
	 * 	- If $b[key] contains values $a[key] does not, `key` is not present in the result
	 * 
	 * @param array $a
	 * @param array $b
	 * @param int $flags
	 * @return array
	 */
	public static function array_diff_assoc($a, $b, $flags = 0) {
		if (!($flags & self::ARRAY_DIFF_ORDERED_ARRAYS)) { //Treat $a and $b as unordered if they're non-associative
			if (!self::is_assoc($a) && !self::is_assoc($b)) {
				sort($a);
				sort($b);
			}
		}
		
		$result = array();
		foreach ($a as $k => $v) {
			if (array_key_exists($k, $b)) {
				if ($a[$k] == $b[$k]) {
					continue;
				}
				
				if (is_array($a[$k]) && is_array($b[$k])) {
					$diff = self::array_diff($a[$k], $b[$k]);
					if (!empty($diff['removed'])) {
						$result[$k] = $diff['removed'];
					}
					
					continue;
				}
			}
			
			$result[$k] = $v;
		}
		return $result;
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
	public static function array_choose($array, $keys, $single = false, $default = null) {
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		
		if (is_object($array) && (
			$array instanceof ArrayAccess ||
				$array instanceof Traversable ||
				$array instanceof Serializable ||
				$array instanceof Countable)) {
			$array = (array) $array;
		}
		
		$matches = array_filter($array, function($k) use ($keys) {
			return in_array($k, $keys);
		}, ARRAY_FILTER_USE_KEY);
		if ($single) {
			$key = self::array_first($keys);
			if ($key !== null && isset($matches[$key])) {
				return $matches[$key];
			}
			
			return $default;
		}
		return $matches;
	}
	
	/**
	 * Convenience function for `array_choose` in its single return value mode for better code readability.
	 * 
	 * @param array $array
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed
	 */
	public static function array_get($array, $key, $default = null) {
		return self::array_choose($array, $key, true, $default);
	}
	
	/**
	 * Polyfill for array_key_first.
	 * 
	 * @param array $array
	 * @return mixed|null
	 */
	public static function array_key_first($array) {
		if (function_exists('array_key_first')) {
			return array_key_first($array);
		}
		
		if (!count($array)) {
			return null;
		}
		
		$keys = array_keys($array);
		return $keys[0];
	}
	
	/**
	 * Polyfill for array_key_last.
	 *
	 * @param array $array
	 * @return mixed|null
	 */
	public static function array_key_last($array) {
		if (function_exists('array_key_last')) {
			return array_key_last($array);
		}
		
		if (!count($array)) {
			return null;
		}
		
		$keys = array_keys($array);
		return $keys[count($keys) - 1];
	}
	
	/**
	 * Performs an array_map but then converts the response into an associative array. $callable is expected to return
	 * [$key => $value] rather than just $value as a normal array_map call would. The resulting array will be as if each
	 * were merged in, preserving the $value under $key. Each $key _should_ generally be unique, but if there are 
	 * duplicates, the last key/value pair mapped for a given $key will be the final value in the array.
	 * 
	 * @param callable $callable
	 * @param array $array
	 * @return array
	 */
	public static function array_kmap($callable, $array) {
		$intermediate = array_map($callable, $array);
		$result = array();
		foreach ($intermediate as $i) { //Can't use array_merge because it discards numerical keys
			$k = self::array_key_first($i);
			$v = $i[$k];
			$result[$k] = $v;
		}
		return $result;
	}
	
	
	/**
	 * Returns whether or not $a is an associative-array. It is considered associative only when the array keys are not
	 * sequential integers starting at 0.
	 * 
	 * @param array $a
	 * @return bool
	 */
	public static function is_assoc($a) {
		if (!is_array($a)) { return false; }
		for ($i = 0; $i < count($a); $i++) {
			if (!isset($a[$i])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns the raw HTTP POST body if possible. This is functionally identical to the implementation in wfWAFUtils 
	 * but present here to avoid complications with nested install WAF optimization. 
	 * 
	 * @return string
	 */
	public static function rawPOSTBody() {
		// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		global $HTTP_RAW_POST_DATA;
		// phpcs:ignore PHPCompatibility.Variables.RemovedPredefinedGlobalVariables.http_raw_post_dataDeprecatedRemoved
		if (empty($HTTP_RAW_POST_DATA)) { //Defined if always_populate_raw_post_data is on, PHP < 7, and the encoding type is not multipart/form-data
			$avoidPHPInput = wfWAFConfig::get('avoid_php_input', false);
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
	 * Convert CIDR notation to a wfUserIPRange object
	 *
	 * @param string $cidr
	 * @return wfUserIPRange
	 */
	public static function CIDR2wfUserIPRange($cidr) {
		list($network, $prefix) = array_pad(explode('/', $cidr, 2), 2, null);
		$ip_range = new wfUserIPRange();

		if (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			// If no prefix was supplied, 32 is implied for IPv4
			if ($prefix === null) {
				$prefix = 32;
			}

			// Validate the IPv4 network prefix
			if ($prefix < 0 || $prefix > 32) {
				return $ip_range;
			}

			// Increase the IPv4 network prefix to work in the IPv6 address space
			$prefix += 96;
		} else {
			// If no prefix was supplied, 128 is implied for IPv6
			if ($prefix === null) {
				$prefix = 128;
			}

			// Validate the IPv6 network prefix
			if ($prefix < 1 || $prefix > 128) {
				return $ip_range;
			}
		}

		// Convert human readable address to 128 bit (IPv6) binary string
		// Note: self::inet_pton converts IPv4 addresses to IPv6 compatible versions
		$binary_network = self::inet_pton($network);
		$binary_mask = wfHelperBin::str2bin(str_pad(str_repeat('1', $prefix), 128, '0', STR_PAD_RIGHT));

		// Calculate first and last address
		$binary_first = $binary_network & $binary_mask;
		$binary_last = $binary_network | ~ $binary_mask;

		// Convert binary addresses back to human readable strings
		$first = self::inet_ntop($binary_first);
		$last = self::inet_ntop($binary_last);

		if (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$first = self::expandIPv6Address($first);
			$last = self::expandIPv6Address($last);
		}

		// Split addresses into segments
		$first_array = preg_split('/[\.\:]/', $first);
		$last_array = preg_split('/[\.\:]/', $last);

		// Make sure arrays are the same size. IPv6 '::' could cause problems otherwise.
		// The strlen filter should leave zeros in place
		$first_array = array_pad(array_filter($first_array, 'strlen'), count($last_array), '0');

		$range_segments = array();

		foreach ($first_array as $index => $segment) {
			if ($segment === $last_array[$index]) {
				$range_segments[] = str_pad(ltrim($segment, '0'), 1, '0');
			} else if ($segment === '' || $last_array[$index] === '') {
				$range_segments[] = '';
			} else {
				$range_segments[] = "[". str_pad(ltrim($segment, '0'), 1, '0') . "-" .
					str_pad(ltrim($last_array[$index], '0'), 1, '0') . "]";
			}
		}

		$delimiter = filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? '.' : ':';

		$ip_range->setIPString(implode($delimiter, $range_segments));

		return $ip_range;
	}

	/**
	 * Return dot notation of IPv4 address.
	 *
	 * @param int $ip
	 * @return string|bool
	 */
	public static function inet_ntoa($ip) {
		$long = 4294967295 - ($ip - 1);
		return long2ip(-$long);
	}

	/**
	 * Return string representation of 32 bit int of the IP address.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function inet_aton($ip) {
		try {
			$ip = preg_replace('/(?<=^|\.)0+([1-9])/', '$1', $ip);
			return sprintf("%u", ip2long($ip));
		}
		catch (Throwable $t) {
			//Ignore -- fall through to default
		}
		return '0';
	}

	/**
	 * Return dot or colon notation of IPv4 or IPv6 address.
	 *
	 * @param string $ip
	 * @return string|bool
	 */
	public static function inet_ntop($ip) {
		// trim this to the IPv4 equiv if it's in the mapped range
		if (strlen($ip) == 16 && substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
			$ip = substr($ip, 12, 4);
		}
		return self::hasIPv6Support() ? @inet_ntop($ip) : self::_inet_ntop($ip);
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
			$pton = str_pad(self::hasIPv6Support() ? @inet_pton($ip) : self::_inet_pton($ip), 16, $default, STR_PAD_LEFT);
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
			$colon_count = substr_count($ip, ':');
			$dbl_colon_pos = strpos($ip, '::');
			if ($dbl_colon_pos !== false) {
				$ip = str_replace('::', str_repeat(':0000',
						(($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
				$ip = trim($ip, ':');
			}

			$ip_groups = explode(':', $ip);
			$ipv6_bin = '';
			foreach ($ip_groups as $ip_group) {
				$ipv6_bin .= pack('H*', str_pad($ip_group, 4, '0', STR_PAD_LEFT));
			}

			return strlen($ipv6_bin) === 16 ? $ipv6_bin : false;
		}

		// IPv4 mapped IPv6
		if (preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $matches)) {
			$octets = explode('.', $matches[1]);
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
		if (strlen($ip) === 4) {
			return ord($ip[0]) . '.' . ord($ip[1]) . '.' . ord($ip[2]) . '.' . ord($ip[3]);
		}

		// IPv6
		if (strlen($ip) === 16) {

			// IPv4 mapped IPv6
			if (substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
				return "::ffff:" . ord($ip[12]) . '.' . ord($ip[13]) . '.' . ord($ip[14]) . '.' . ord($ip[15]);
			}

			$hex = bin2hex($ip);
			$groups = str_split($hex, 4);
			$in_collapse = false;
			$done_collapse = false;
			foreach ($groups as $index => $group) {
				if ($group == '0000' && !$done_collapse) {
					if ($in_collapse) {
						$groups[$index] = '';
						continue;
					}
					$groups[$index] = ':';
					$in_collapse = true;
					continue;
				}
				if ($in_collapse) {
					$done_collapse = true;
				}
				$groups[$index] = ltrim($groups[$index], '0');
				if (strlen($groups[$index]) === 0) {
					$groups[$index] = '0';
				}
			}
			$ip = join(':', array_filter($groups, 'strlen'));
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
			$hasSupport = defined('AF_INET6') && self::funcEnabled('inet_ntop') && self::funcEnabled('inet_pton');
		}
		return $hasSupport;
	}

	public static function hasLoginCookie(){
		if(isset($_COOKIE)){
			if(is_array($_COOKIE)){
				foreach($_COOKIE as $key => $val){
					if(strpos($key, 'wordpress_logged_in') === 0){
						return true;
					}
				}
			}
		}
		return false;
	}
	public static function getBaseURL(){
		return plugins_url('', WORDFENCE_FCPATH) . '/';
	}
	public static function getPluginBaseDir(){
		if(function_exists('wp_normalize_path')){ //Older WP versions don't have this func and we had many complaints before this check.
			if(defined('WP_PLUGIN_DIR')) {
				return wp_normalize_path(WP_PLUGIN_DIR . '/');
			}
			return wp_normalize_path(WP_CONTENT_DIR . '/plugins/');
		} else {
			if(defined('WP_PLUGIN_DIR')) {
				return WP_PLUGIN_DIR . '/';
			}
			return WP_CONTENT_DIR . '/plugins/';
		}
	}
	
	/**
	 * Convenience function to generate an admin URL using the appropriate function depending on multisite status.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function maybeNetworkAdminURL($path) {
		return function_exists('network_admin_url') && is_multisite() ? network_admin_url($path) : admin_url($path);
	}
	public static function makeRandomIP(){
		return rand(11,230) . '.' . rand(0,255) . '.' . rand(0,255) . '.' . rand(0,255);
	}
	
	/**
	 * Converts a truthy value to a boolean, checking in this order:
	 * - already a boolean
	 * - is null => false
	 * - numeric (0 => false, otherwise true)
	 * - 'false', 'f', 'no', 'n', or 'off' => false
	 * - 'true', 't', 'yes', 'y', or 'on' => true
	 * - empty value => false, otherwise true
	 * 
	 * @param $value
	 * @return bool
	 */
	public static function truthyToBoolean($value) {
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
	
	/**
	 * Converts a truthy value to 1 or 0.
	 * 
	 * @see wfUtils::truthyToBoolean
	 * 
	 * @param $value
	 * @return int
	 */
	public static function truthyToInt($value) {
		return self::truthyToBoolean($value) ? 1 : 0;
	}
	
	/**
	 * Returns the whitelist presets, which first grabs the bundled list and then merges the dynamic list into it.
	 * 
	 * @return array
	 */
	public static function whitelistPresets() {
		static $_cachedPresets = null;
		if ($_cachedPresets === null) {
			include(dirname(__FILE__) . '/wfIPWhitelist.php'); /** @var array $wfIPWhitelist */
			$currentPresets = wfConfig::getJSON('whitelistPresets', array());
			if (is_array($currentPresets)) {
				$_cachedPresets = array_merge($wfIPWhitelist, $currentPresets);
			}
			else {
				$_cachedPresets = $wfIPWhitelist;
			}
		}
		return $_cachedPresets;
	}
	
	/**
	 * Returns an array containing all whitelisted service IPs/ranges. The returned array is grouped by service
	 * tag: array('service1' => array('range1', 'range2', range3', ...), ...)
	 * 
	 * @param array|null $whitelistedServices If provided, use this service list for enabled/disabled resolution
	 * @return array
	 */
	public static function whitelistedServiceIPs($whitelistedServices = null) {
		$result = array();
		$whitelistPresets = self::whitelistPresets();
		if ($whitelistedServices === null) {
			$whitelistedServices = wfConfig::getJSON('whitelistedServices', array());
		}
		foreach ($whitelistPresets as $tag => $preset) {
			if (!isset($preset['n'])) { //Just an array of IPs/ranges
				$result[$tag] = $preset;
				continue;
			}
			
			if ((isset($preset['h']) && $preset['h']) || (isset($preset['f']) && $preset['f'])) { //Forced
				$result[$tag] = $preset['r'];
				continue;
			}
			
			if ((!isset($whitelistedServices[$tag]) && isset($preset['d']) && $preset['d']) || (isset($whitelistedServices[$tag]) && $whitelistedServices[$tag])) {
				$result[$tag] = $preset['r'];
			}
		}
		return $result;
	}

	/**
	 * Get the list of whitelisted IPs and networks, which is a combination of preset IPs/ranges and user-entered
	 * IPs/ranges.
	 *
	 * @param string	$filter	Group name to filter whitelist by
	 * @return array
	 */
	public static function getIPWhitelist($filter = null) {
		static $wfIPWhitelist;

		if (!isset($wfIPWhitelist)) {
			$wfIPWhitelist = self::whitelistedServiceIPs();
			
			//Append user ranges
			$wfIPWhitelist['user'] = array();
			foreach (array_filter(explode(',', wfConfig::get('whitelisted'))) as $ip) {
				$wfIPWhitelist['user'][] = new wfUserIPRange($ip);
			}
		}

		$whitelist = array();
		foreach ($wfIPWhitelist as $group => $values) {
			if ($filter === null || $group === $filter) {
				$whitelist = array_merge($whitelist, $values);
			}
		}

		return $whitelist;
	}

	/**
	 * @param string $addr Should be in dot or colon notation (127.0.0.1 or ::1)
	 * @return bool
	 */
	public static function isPrivateAddress($addr) {
		// Run this through the preset list for IPv4 addresses.
		if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			foreach (self::getIPWhitelist('private') as $a) {
				if (self::subnetContainsIP($a, $addr)) {
					return true;
				}
			}
		}

		return filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false
			&& filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
	}

	/**
	 * Expects an array of items. The items are either IP's or IP's separated by comma, space or tab. Or an array of IP's.
	 * We then examine all IP's looking for a public IP and storing private IP's in an array. If we find no public IPs we return the first private addr we found.
	 *
	 * @param array $arr
	 * @return bool|mixed
	 */
	private static function getCleanIP($arr){
		$privates = array(); //Store private addrs until end as last resort.
		for($i = 0; $i < count($arr); $i++){
			$item = $arr[$i];
			if(is_array($item)){
				foreach($item as $j){
					// try verifying the IP is valid before stripping the port off
					if (!self::isValidIP($j)) {
						$j = preg_replace('/:\d+$/', '', $j); //Strip off port
					}
					if (self::isValidIP($j)) {
						if (self::isPrivateAddress($j)) {
							$privates[] = $j;
						} else {
							return $j;
						}
					}
				}
				continue; //This was an array so we can skip to the next item
			}
			$skipToNext = false;
			foreach(array(',', ' ', "\t") as $char){
				if(strpos($item, $char) !== false){
					$sp = explode($char, $item);
					$sp = array_reverse($sp);
					foreach($sp as $j){
						$j = trim($j);
						if (!self::isValidIP($j)) {
							$j = preg_replace('/:\d+$/', '', $j); //Strip off port
						}
						if(self::isValidIP($j)){
							if(self::isPrivateAddress($j)){
								$privates[] = $j;
							} else {
								return $j;
							}
						}
					}
					$skipToNext = true;
					break;
				}
			}
			if($skipToNext){ continue; } //Skip to next item because this one had a comma, space or tab so was delimited and we didn't find anything.

			if (!self::isValidIP($item)) {
				$item = preg_replace('/:\d+$/', '', $item); //Strip off port
			}
			if(self::isValidIP($item)){
				if(self::isPrivateAddress($item)){
					$privates[] = $item;
				} else {
					return $item;
				}
			}
		}
		if(sizeof($privates) > 0){
			return $privates[0]; //Return the first private we found so that we respect the order the IP's were passed to this function.
		} else {
			return false;
		}
	}

	/**
	 * Expects an array of items. The items are either IP's or IP's separated by comma, space or tab. Or an array of IP's.
	 * We then examine all IP's looking for a public IP and storing private IP's in an array. If we find no public IPs we return the first private addr we found.
	 *
	 * @param array $arr
	 * @return bool|mixed
	 */
	private static function getCleanIPAndServerVar($arr, $trustedProxies = null) {
		$privates = array(); //Store private addrs until end as last resort.
		for($i = 0; $i < count($arr); $i++){
			list($item, $var) = $arr[$i];
			if(is_array($item)){
				foreach($item as $j){
					// try verifying the IP is valid before stripping the port off
					if (!self::isValidIP($j)) {
						$j = preg_replace('/:\d+$/', '', $j); //Strip off port
					}
					if (self::isValidIP($j)) {
						if (self::isIPv6MappedIPv4($j)) {
							$j = self::inet_ntop(self::inet_pton($j));
						}

						if (self::isPrivateAddress($j)) {
							$privates[] = array($j, $var);
						} else {
							return array($j, $var);
						}
					}
				}
				continue; //This was an array so we can skip to the next item
			}
			$skipToNext = false;
			if ($trustedProxies === null) {
				$trustedProxies = self::unifiedTrustedProxies();
			}
			foreach(array(',', ' ', "\t") as $char){
				if(strpos($item, $char) !== false){
					$sp = explode($char, $item);
					$sp = array_reverse($sp);
					foreach($sp as $index => $j){
						$j = trim($j);
						if (!self::isValidIP($j)) {
							$j = preg_replace('/:\d+$/', '', $j); //Strip off port
						}
						if(self::isValidIP($j)){
							if (self::isIPv6MappedIPv4($j)) {
								$j = self::inet_ntop(self::inet_pton($j));
							}
							
							foreach ($trustedProxies as $proxy) {
								if (!empty($proxy)) {
									if (self::subnetContainsIP($proxy, $j) && $index < count($sp) - 1) {
										continue 2;
									}
								}
							}

							if(self::isPrivateAddress($j)){
								$privates[] = array($j, $var);
							} else {
								return array($j, $var);
							}
						}
					}
					$skipToNext = true;
					break;
				}
			}
			if($skipToNext){ continue; } //Skip to next item because this one had a comma, space or tab so was delimited and we didn't find anything.

			if (!self::isValidIP($item)) {
				$item = preg_replace('/:\d+$/', '', $item); //Strip off port
			}
			if(self::isValidIP($item)){
				if (self::isIPv6MappedIPv4($item)) {
					$item = self::inet_ntop(self::inet_pton($item));
				}

				if(self::isPrivateAddress($item)){
					$privates[] = array($item, $var);
				} else {
					return array($item, $var);
				}
			}
		}
		if(sizeof($privates) > 0){
			return $privates[0]; //Return the first private we found so that we respect the order the IP's were passed to this function.
		} else {
			return false;
		}
	}
	
	/**
	 * Returns an array of all trusted proxies, combining both the user-entered ones and those from the selected preset.
	 * 
	 * @return string[]
	 */
	public static function unifiedTrustedProxies() {
		$trustedProxies = explode("\n", wfConfig::get('howGetIPs_trusted_proxies', ''));
		
		$preset = wfConfig::get('howGetIPs_trusted_proxy_preset');
		$presets = wfConfig::getJSON('ipResolutionList', array());
		if (is_array($presets) && isset($presets[$preset])) {
			$testIPs = array_merge($presets[$preset]['ipv4'], $presets[$preset]['ipv6']);
			foreach ($testIPs as $val) {
				if (strlen($val) > 0) {
					if (wfUtils::isValidIP($val) || wfUtils::isValidCIDRRange($val)) {
						$trustedProxies[] = $val;
					}
				}
			}
		}
		return $trustedProxies;
	}

	/**
	 * @param string $ip
	 * @return bool
	 */
	public static function isIPv6MappedIPv4($ip) {
		return preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $ip) > 0;
	}

	public static function extractHostname($str){
		if(preg_match('/https?:\/\/([a-zA-Z0-9\.\-]+)(?:\/|$)/i', $str, $matches)){
			return strtolower($matches[1]);
		} else {
			return false;
		}
	}
	
	/**
	 * Returns the known server IPs, ordered by those as the best match for outgoing requests.
	 * 
	 * @param bool $refreshCache
	 * @return string[]
	 */
	public static function serverIPs($refreshCache = false) {
		static $cachedServerIPs = null;
		if (isset($cachedServerIPs) && !$refreshCache) {
			return $cachedServerIPs;
		}
		
		$serverIPs = array();
		$storedIP = wfConfig::get('serverIP');
		if (preg_match('/^(\d+);(.+)$/', $storedIP, $matches)) { //Format is 'timestamp;ip'
			$serverIPs[] = $matches[2];
		}
		
		if (function_exists('dns_get_record')) {
			$storedDNS = wfConfig::get('serverDNS');
			$usingCache = false;
			if (preg_match('/^(\d+);(\d+);(.+)$/', $storedDNS, $matches)) { //Format is 'timestamp;ttl;ip'
				$timestamp = $matches[1];
				$ttl = $matches[2];
				if ($timestamp + max($ttl, 86400) > time()) {
					$serverIPs[] = $matches[3];
					$usingCache = true;
				}
			}
			
			if (!$usingCache) {
				$home = get_home_url();
				if (preg_match('/^https?:\/\/([^\/]+)/i', $home, $matches)) {
					$host = strtolower($matches[1]);
					$cnameRaw = @dns_get_record($host, DNS_CNAME);
					$cnames = array();
					$cnamesTargets = array();
					if ($cnameRaw && is_array($cnameRaw)) {
						foreach ($cnameRaw as $elem) {
							if (is_array($elem) && 
								array_key_exists('host', $elem) && 
								array_key_exists('target', $elem) && 
								$elem['host'] == $host
							) {
								$cnames[] = $elem;
								$cnamesTargets[] = $elem['target'];
							}
						}
					}
					
					$aRaw = @dns_get_record($host, DNS_A);
					$a = array();
					if ($aRaw && is_array($aRaw)) {
						foreach ($aRaw as $elem) {
							if (is_array($elem) && 
								array_key_exists('host', $elem) &&
								array_key_exists('ttl', $elem) &&
								array_key_exists('ip', $elem) &&
								($elem['host'] == $host || in_array($elem['host'], $cnamesTargets))
							) {
								$a[] = $elem;
							}
						}
					}
					
					$firstA = wfUtils::array_first($a);
					if ($firstA !== null) {
						$serverIPs[] = $firstA['ip'];
						wfConfig::set('serverDNS', time() . ';' . $firstA['ttl'] . ';' . $firstA['ip']);
					}
				}
			}
		}
		
		/*
		 * This segment governs the SERVER_ADDR cache. We cache all seen values for the TTL defined by 
		 * `wfUtils::SERVER_ADDR_CACHE_TTL` to account for varying IPs from load balancers. We do not refresh
		 * the timestamp on a repeat address until it's at least `wfUtils::SERVER_ADDR_REFRESH_TTL` old unless we're
		 * going to save a change anyway. 
		 */
		
		$recentServerAddr = wfConfig::get_ser('recentServerAddr');
		$dirtyServerAddr = false;
		$containsCurrentServerAddr = false;
		$recents = array();
		if (!is_array($recentServerAddr)) {
			$recentServerAddr = array();
		}
		
		$recentServerAddr = array_filter($recentServerAddr, function($time, $addr) use (&$dirtyServerAddr, &$containsCurrentServerAddr, &$recents) {
			if (!wfUtils::isValidIP($addr)) {
				$dirtyServerAddr = true;
				return false;
			}
			
			if ($time < time() - self::SERVER_ADDR_CACHE_TTL) {
				$dirtyServerAddr = true;
				return false;
			}
			
			if (isset($_SERVER['SERVER_ADDR']) && $addr == $_SERVER['SERVER_ADDR']) {
				$containsCurrentServerAddr = true;
				if ($time < time() - self::SERVER_ADDR_REFRESH_TTL) {
					$dirtyServerAddr = true;
				}
				return false; //Filtering out the current one now so it can be re-added later with a current timestamp
			}
			$recents[] = $addr;
			return true;
		}, ARRAY_FILTER_USE_BOTH);
		
		if (isset($_SERVER['SERVER_ADDR']) && wfUtils::isValidIP($_SERVER['SERVER_ADDR'])) {
			$recentServerAddr[$_SERVER['SERVER_ADDR']] = time();
			$recents[] = $_SERVER['SERVER_ADDR'];
			$dirtyServerAddr = $dirtyServerAddr || !$containsCurrentServerAddr;
		}
			
		if ($dirtyServerAddr) {
			//Check for state change from another request, merge as needed, then save our update
			$locked = wfConfig::createLock('recentServerAddr');
			if ($locked) {
				$currentState = wfConfig::get_ser('recentServerAddr', array(), true, false);
				if (is_array($currentState)) {
					foreach ($currentState as $addr => $time) {
						if (!wfUtils::isValidIP($addr)) {
							continue;
						}
						
						if (array_key_exists($addr, $recentServerAddr)) {
							$recentServerAddr[$addr] = max($time, $recentServerAddr[$addr]);
						}
						else if ($time > time() - self::SERVER_ADDR_CACHE_TTL) {
							$recentServerAddr[$addr] = $time;
							$recents[] = $addr;
						}
					}
				}
				
				wfConfig::set_ser('recentServerAddr', $recentServerAddr);
				wfConfig::releaseLock('recentServerAddr');
			}
		}
		
		sort($recents);
		$serverIPs = array_merge($serverIPs, $recents);
		
		$serverIPs = array_values(array_unique($serverIPs));
		$cachedServerIPs = $serverIPs;
		return $serverIPs;
	}
	
	public static function getIP($refreshCache = false) {
		static $theIP = null;
		if (isset($theIP) && !$refreshCache) {
			return $theIP;
		}
		//For debugging. 
		//return '54.232.205.132';
		//return self::makeRandomIP();

		// if no REMOTE_ADDR, it's probably running from the command line
		$ip = self::getIPAndServerVariable();
		if (is_array($ip)) {
			list($IP, $variable) = $ip;
			$theIP = $IP;
			return $IP;
		}
		return false;
	}
	
	public static function getIPForField($field, $trustedProxies = null) {
		$ip = self::getIPAndServerVariable($field, $trustedProxies);
		if (is_array($ip)) {
			list($IP, $variable) = $ip;
			return $IP;
		}
		return false;
	}

	public static function getAllServerVariableIPs()
	{
		$variables = array('REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR');
		$ips = array();

		foreach ($variables as $variable) {
			$ip = isset($_SERVER[$variable]) ? $_SERVER[$variable] : false;

			if ($ip && strpos($ip, ',') !== false) {
				$ips[$variable] = preg_replace('/[\s,]/', '', explode(',', $ip));
			} else {
				$ips[$variable] = $ip;
			}
		}

		return $ips;
	}

	public static function getIPAndServerVariable($howGet = null, $trustedProxies = null) {
		$connectionIP = array_key_exists('REMOTE_ADDR', $_SERVER) ? array($_SERVER['REMOTE_ADDR'], 'REMOTE_ADDR') : array('127.0.0.1', 'REMOTE_ADDR');

		if ($howGet === null) {
			$howGet = wfConfig::get('howGetIPs', false);
		}
		
		if($howGet){
			if($howGet == 'REMOTE_ADDR'){
				return self::getCleanIPAndServerVar(array($connectionIP), $trustedProxies);
			} else {
				$ipsToCheck = array(
					array((isset($_SERVER[$howGet]) ? $_SERVER[$howGet] : ''), $howGet),
					$connectionIP,
				);
				return self::getCleanIPAndServerVar($ipsToCheck, $trustedProxies);
			}
		} else {
			$ipsToCheck = array();
			
			$recommendedField = wfConfig::get('detectProxyRecommendation', ''); //Prioritize the result from our proxy check if done
			if (!empty($recommendedField) && $recommendedField != 'UNKNOWN' && $recommendedField != 'DEFERRED') {
				if (isset($_SERVER[$recommendedField])) {
					$ipsToCheck[] = array($_SERVER[$recommendedField], $recommendedField);
				}
			}
			$ipsToCheck[] = $connectionIP;
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ipsToCheck[] = array($_SERVER['HTTP_X_FORWARDED_FOR'], 'HTTP_X_FORWARDED_FOR');
			}
			if (isset($_SERVER['HTTP_X_REAL_IP'])) {
				$ipsToCheck[] = array($_SERVER['HTTP_X_REAL_IP'], 'HTTP_X_REAL_IP');
			}
			return self::getCleanIPAndServerVar($ipsToCheck, $trustedProxies);
		}
		return false; //Returns an array with a valid IP and the server variable, or false.
	}
	
	/**
	 * Returns an array of IPs seen by the server. The structure of the return value is an array of arrays where each
	 * child array has the structure ['ip' => <ip address>, 'selected' => <whether this one is considered the client ip>]
	 *
	 * @return array|false May return false if there are no IPs (e.g., a CLI request)
	 */
	public static function getIPPreview($howGet = null, $trustedProxies = null) {
		$ip = self::getIPAndServerVariable($howGet, $trustedProxies);
		if (is_array($ip)) {
			list($IP, $variable) = $ip;
			if (isset($_SERVER[$variable]) && strpos($_SERVER[$variable], ',') !== false) {
				$items = preg_replace('/[\s,]/', '', explode(',', $_SERVER[$variable]));
				$output = array();
				foreach ($items as $i) {
					if ($IP == $i) {
						$output[] = array('ip' => $i, 'selected' => true);
					}
					else {
						$output[] = array('ip' => $i, 'selected' => false);
					}
				}
				
				return $output;
			}
			return array(array('ip' => $IP, 'selected' => true));
		}
		return false;
	}
	public static function isValidIP($IP){
		return filter_var($IP, FILTER_VALIDATE_IP) !== false;
	}
	public static function isValidCIDRRange($range) {
		$components = explode('/', $range);
		if (count($components) != 2) { return false; }
		
		list($ip, $prefix) = $components;
		if (!self::isValidIP($ip)) { return false; }
		
		if (!preg_match('/^\d+$/', $prefix)) { return false; }
		
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if ($prefix < 0 || $prefix > 32) { return false; }
		}
		else {
			if ($prefix < 1 || $prefix > 128) { return false; }
		}
		
		return true;
	}
	public static function isValidEmail($email, $strict = false) {
		if (empty($email)) {
			return false;
		}
		
		//We don't default to strict, full validation because poorly-configured servers can crash due to the regex PHP uses in filter_var($email, FILTER_VALIDATE_EMAIL)
		if ($strict) {
			return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
		}
		
		return preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/i', $email) === 1;
	}
	public static function getRequestedURL() {
		if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) {
			$host = $_SERVER['HTTP_HOST'];
		} else if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME']) {
			$host = $_SERVER['SERVER_NAME'];
		}
		else {
			return null;
		}
		$prefix = 'http';
		if (is_ssl()) {
			$prefix = 'https';
		}
		return $prefix . '://' . $host . $_SERVER['REQUEST_URI'];
	}

	public static function editUserLink($userID){
		return get_admin_url() . 'user-edit.php?user_id=' . $userID;
	}
	public static function tmpl($file, $data){
		extract($data);
		ob_start();
		include dirname(__FILE__) . DIRECTORY_SEPARATOR . $file;
		return ob_get_contents() . (ob_end_clean() ? "" : "");
	}
	public static function bigRandomHex(){
		return bin2hex(wfWAFUtils::random_bytes(16));
	}
	public static function encrypt($str){
		$key = wfConfig::get('encKey');
		if(! $key){
			wordfence::status(1, 'error', __("Wordfence error: No encryption key found!", 'wordfence'));
			return false;
		}
		$db = new wfDB();
		return $db->querySingle("select HEX(AES_ENCRYPT('%s', '%s')) as val", $str, $key);
	}
	public static function decrypt($str){
		$key = wfConfig::get('encKey');
		if(! $key){
			wordfence::status(1, 'error', __("Wordfence error: No encryption key found!", 'wordfence'));
			return false;
		}
		$db = new wfDB();
		return $db->querySingle("select AES_DECRYPT(UNHEX('%s'), '%s') as val", $str, $key);
	}
	public static function lcmem(){
		$trace=debug_backtrace();
		$caller=array_shift($trace);
		$mem = memory_get_usage(true);
		error_log("$mem at " . $caller['file'] . " line " . $caller['line']);
	}
	public static function logCaller(){
		$trace=debug_backtrace();
		$caller=array_shift($trace);
		$c2 = array_shift($trace);
		error_log("Caller for " . $caller['file'] . " line " . $caller['line'] . " is " . $c2['file'] . ' line ' . $c2['line']);
	}
	public static function getWPVersion($forceRecheck = false){
		if ($forceRecheck) {
			require(ABSPATH . 'wp-includes/version.php'); //defines $wp_version
			return $wp_version;
		}
		
		if(wordfence::$wordfence_wp_version){
			return wordfence::$wordfence_wp_version;
		} else {
			global $wp_version;
			return $wp_version;
		}
	}
	
	public static function parse_version($version, $component = null) {
		$major = 0;
		$minor = 0;
		$patch = 0;
		$prerelease = '';
		$build = '';
		
		if (preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/', $version, $matches)) { //semver
			$major = $matches[1];
			$minor = $matches[2];
			$patch = $matches[3];
			
			if (preg_match('/^([^\+]+)\+(.*)$/', $version, $matches)) {
				$version = $matches[1];
				$build = $matches[2];
			}
			
			if (preg_match('/^([^\-]+)\-(.*)$/', $version, $matches)) {
				$version = $matches[1];
				$prerelease = $matches[2];
			}
		}
		else { //Parse as "PHP-standardized" (see version_compare docs: "The function first replaces _, - and + with a dot . in the version strings and also inserts dots . before and after any non number so that for example '4.3.2RC1' becomes '4.3.2.RC.1'.")
			$version = trim(preg_replace('/\.\.+/', '.', preg_replace('/([^0-9\.]+)/', '.$1.', preg_replace('/[_\-\+]+/', '.', $version))), '.');
			$components = explode('.', $version);
			$i = 0;
			if (isset($components[$i]) && is_numeric($components[$i])) { $major = $components[$i]; $i++; }
			if (isset($components[$i]) && is_numeric($components[$i])) { $minor = $components[$i]; $i++; }
			if (isset($components[$i]) && is_numeric($components[$i])) { $patch = $components[$i]; $i++; }
			while (isset($components[$i]) && is_numeric($components[$i])) {
				if (!empty($build)) {
					$build .= '.';
				}
				$build .= $components[$i];
				$i++;
			}
			while (isset($components[$i])) {
				if (!empty($prerelease)) {
					$prerelease .= '.';
				}
				
				if (preg_match('/^(?:dev|alpha|a|beta|b|rc|#|pl|p)$/i', $components[$i])) {
					$prerelease .= strtolower($components[$i]);
					if (isset($components[$i + 1])) {
						if (!preg_match('/^(?:a|b|rc|#|pl|p)$/i', $components[$i])) {
							$prerelease .= '-';
						}
						$i++;
					}
				}
				
				$prerelease .= $components[$i];
				$i++;
			}
		}
		
		$version = array(
			self::VERSION_MAJOR => $major,
			self::VERSION_MINOR => $minor,
			self::VERSION_PATCH => $patch,
			self::VERSION_PRE_RELEASE => $prerelease,
			self::VERSION_BUILD => $build,
		);
		
		$version = array_filter($version, function($v) {
			return $v !== '';
		});
		
		if ($component === null) {
			return $version;
		}
		else if (isset($version[$component])) {
			return $version[$component];
		}
		
		return null;
	}
	
	public static function isAdminPageMU(){
		if(preg_match('/^[\/a-zA-Z0-9\-\_\s\+\~\!\^\.]*\/wp-admin\/network\//', $_SERVER['REQUEST_URI'])){
			return true;
		}
		return false;
	}
	public static function getSiteBaseURL(){
		return rtrim(site_url(), '/') . '/';
	}
	public static function longestLine($data){
		$lines = preg_split('/[\r\n]+/', $data);
		$max = 0;
		foreach($lines as $line){
			$len = strlen($line);
			if($len > $max){
				$max = $len;
			}
		}
		return $max;
	}
	public static function longestNospace($data){
		$lines = preg_split('/[\r\n\s\t]+/', $data);
		$max = 0;
		foreach($lines as $line){
			$len = strlen($line);
			if($len > $max){
				$max = $len;
			}
		}
		return $max;
	}
	
	/**
	 * Returns the current PHP memory limit in bytes.
	 * 
	 * Note: This is duplicated in wordfence.php to avoid needing to include this class early.
	 * 
	 * @return int
	 */
	public static function memoryLimit() {
		$maxMemory = ini_get('memory_limit');
		if (!(is_string($maxMemory) || is_numeric($maxMemory)) || !preg_match('/^\s*\d+[GMK]?\s*$/i', $maxMemory)) { $maxMemory = '128M'; } //Invalid or unreadable value, default to our minimum
		$last = strtolower(substr($maxMemory, -1));
		$maxMemory = (int) $maxMemory;
		
		if ($last == 'g') { $maxMemory = $maxMemory * 1024 * 1024 * 1024; }
		else if ($last == 'm') { $maxMemory = $maxMemory * 1024 * 1024; }
		else if ($last == 'k') { $maxMemory = $maxMemory * 1024; }
		return floor($maxMemory);
	}
	
	public static function requestMaxMemory() {
		$maxMem = wfConfig::getInt('maxMem', 256);
		if ($maxMem <= 0) {
			$maxMem = 256;
		}
		
		if (self::funcEnabled('memory_get_usage') && self::memoryLimit() < ($maxMem * 1024 * 1024)){
			self::iniSet('memory_limit', $maxMem . 'M');
		}
	}
	
	public static function isAdmin($user = false){
		if($user){
			if(is_multisite()){
				if(user_can($user, 'manage_network')){
					return true;
				}
			} else {
				if(user_can($user, 'manage_options')){
					return true;
				}
			}
		} else {
			if(is_multisite()){
				if(current_user_can('manage_network')){
					return true;
				}
			} else {
				if(current_user_can('manage_options')){
					return true;
				}
			}
		}
		return false;
	}
	public static function hasTwoFactorEnabled($user = false) {
		if (!$user) {
			$user = get_user_by('ID', get_current_user_id());
		}
		
		if (!$user) {
			return false;
		}
		
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		$hasActivatedTwoFactorUser = false;
		foreach ($twoFactorUsers as &$t) {
			if ($t[3] == 'activated') {
				$userID = $t[0];
				if ($userID == $user->ID && wfUtils::isAdmin($user)) {
					$hasActivatedTwoFactorUser = true;
				}
			}
		}
		
		return $hasActivatedTwoFactorUser;
	}
	public static function isWindows(){
		if(! self::$isWindows){
			if(preg_match('/^win/i', PHP_OS)){
				self::$isWindows = 'yes';
			} else {
				self::$isWindows = 'no';
			}
		}
		return self::$isWindows == 'yes' ? true : false;
	}
	public static function cleanupOneEntryPerLine($string) {
		$string = !is_string($string) ? '' : $string;
		$string = str_replace(",", "\n", $string); // fix old format
		return implode("\n", array_unique(array_filter(array_map('trim', explode("\n", $string)))));
	}

	public static function afterProcessingFile() {
		if (wfScanner::shared()->useLowResourceScanning()) {
			usleep(10000); //10 ms
		}
	}

	public static function getScanLock(){
		//Windows does not support non-blocking flock, so we use time.
		$scanRunning = wfConfig::get('wf_scanRunning');
		if($scanRunning && time() - $scanRunning < WORDFENCE_MAX_SCAN_LOCK_TIME){
			return false;
		}
		wfConfig::set('wf_scanRunning', time());
		return true;
	}
	public static function clearScanLock(){
		global $wpdb;
		$wfdb = new wfDB();
		$wfdb->truncate(wfDB::networkTable('wfHoover'));

		wfConfig::set('wf_scanRunning', '');
		wfIssues::updateScanStillRunning(false);
		if (wfCentral::isConnected()) {
			wfCentral::updateScanStatus();
		}
	}
	public static function getIPGeo($IP){ //Works with int or dotted

		$locs = self::getIPsGeo(array($IP));
		if(isset($locs[$IP])){
			return $locs[$IP];
		} else {
			return false;
		}
	}
	public static function getIPsGeo($IPs){ //works with int or dotted. Outputs same format it receives.
		$IPs = array_unique($IPs);
		$toResolve = array();
		$db = new wfDB();
		$locsTable = wfDB::networkTable('wfLocs');
		$IPLocs = array();
		foreach($IPs as $IP){
			$isBinaryIP = !self::isValidIP($IP);
			if ($isBinaryIP) {
				$ip_printable = wfUtils::inet_ntop($IP);
				$ip_bin = $IP;
			} else {
				$ip_printable = $IP;
				$ip_bin = wfUtils::inet_pton($IP);
			}
			
			$ipHex = wfDB::binaryValueToSQLHex($ip_bin);
			$row = $db->querySingleRec("select IP, ctime, failed, city, region, countryName, countryCode, lat, lon, unix_timestamp() - ctime as age from " . $locsTable . " where IP={$ipHex}");
			if($row){
				if($row['age'] > WORDFENCE_MAX_IPLOC_AGE){
					$ipHex = wfDB::binaryValueToSQLHex($row['IP']);
					$db->queryWrite("delete from " . $locsTable . " where IP={$ipHex}");
				} else {
					if($row['failed'] == 1){
						$IPLocs[$ip_printable] = false;
					} else {
						$row['IP'] = self::inet_ntop($row['IP']);
						$row['region'] = wfUtils::shouldDisplayRegion($row['countryName']) ? $row['region'] : '';
						$IPLocs[$ip_printable] = $row;
					}
				}
			}
			if(! isset($IPLocs[$ip_printable])){
				$toResolve[] = $ip_printable;
			}
		}
		if(sizeof($toResolve) > 0){
			if (wfConfig::get('enableRemoteIpLookup', true)) {
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				try {
					$freshIPs = $api->call('resolve_ips', array(), array(
						'ips' => implode(',', $toResolve)
						));
				} catch(Exception $e){
					wordfence::status(2, 'error', sprintf(/* translators: Error message. */ __("Call to Wordfence API to resolve IPs failed: %s", 'wordfence'), $e->getMessage()));
					return array();
				}
			}
			else {
				self::requireIpLocator();
				$locator = wfIpLocator::getInstance();
				$freshIPs = array();
				$locale = get_locale();
				foreach ($toResolve as $ip) {
					$record = $locator->locate($ip);
					if ($record !== null) {
						$countryCode = $record->getCountryCode();
						if ($countryCode !== null) {
							$countryName = $record->getCountryName($locale);
							if ($countryName === null)
								$countryName = $countryCode;
							$freshIPs[$ip] = array($countryCode, $countryName);
							continue;
						}
					}
					$freshIPs[$ip] = 'failed';
				}
			}
			if(is_array($freshIPs)){
				foreach($freshIPs as $IP => $value){
					$IP_bin = wfUtils::inet_pton($IP);
					$ipHex = wfDB::binaryValueToSQLHex($IP_bin);
					if($value == 'failed'){
						$db->queryWrite("insert IGNORE into " . $locsTable . " (IP, ctime, failed) values ({$ipHex}, unix_timestamp(), 1)");
						$IPLocs[$IP] = false;
					} else if(is_array($value)){
						for($i = 0; $i <= 5; $i++){
							//Prevent warnings in debug mode about uninitialized values
							if(! isset($value[$i])){ $value[$i] = ''; }
						}
						$db->queryWrite("insert IGNORE into " . $locsTable . " (IP, ctime, failed, city, region, countryName, countryCode, lat, lon) values ({$ipHex}, unix_timestamp(), 0, '%s', '%s', '%s', '%s', %s, %s)",
							$value[3], //city
							$value[2], //region
							$value[1], //countryName
							$value[0],//countryCode
							$value[4],//lat
							$value[5]//lon
							);
						$IPLocs[$IP] = array(
							'IP' => $IP,
							'city' => $value[3],
							'region' => wfUtils::shouldDisplayRegion($value[1]) ? $value[2] : '',
							'countryName' => $value[1],
							'countryCode' => $value[0],
							'lat' => $value[4],
							'lon' => $value[5]
							);
					}
				}
			}
		}
		return $IPLocs;
	}

	public static function reverseLookup($IP) {
		static $_memoryCache = array();
		if (isset($_memoryCache[$IP])) {
			return $_memoryCache[$IP];
		}
		
		$db = new wfDB();
		$reverseTable = wfDB::networkTable('wfReverseCache');
		$IPn = wfUtils::inet_pton($IP);
		$ipHex = wfDB::binaryValueToSQLHex($IPn);
		$host = $db->querySingle("select host from " . $reverseTable . " where IP={$ipHex} and unix_timestamp() - lastUpdate < %d", WORDFENCE_REVERSE_LOOKUP_CACHE_TIME);
		if (!$host) {
			// This function works for IPv4 or IPv6
			if (function_exists('gethostbyaddr')) {
				$host = @gethostbyaddr($IP);
			}
			if (!$host) {
				$ptr = false;
				if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
					$ptr = implode(".", array_reverse(explode(".", $IP))) . ".in-addr.arpa";
				} else if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
					$ptr = implode(".", array_reverse(str_split(bin2hex($IPn)))) . ".ip6.arpa";
				}

				if ($ptr && function_exists('dns_get_record')) {
					$host = @dns_get_record($ptr, DNS_PTR);
					if ($host && is_array($host) && count($host)) {
						$host = wfUtils::array_get($host[0], 'target');
					}
				}
			}
			$_memoryCache[$IP] = $host;
			if (!$host) {
				$host = 'NONE';
			}
			$db->queryWrite("insert into " . $reverseTable . " (IP, host, lastUpdate) values ({$ipHex}, '%s', unix_timestamp()) ON DUPLICATE KEY UPDATE host='%s', lastUpdate=unix_timestamp()", $host, $host);
		}
		if ($host == 'NONE') {
			$_memoryCache[$IP] = '';
			return '';
		} else {
			$_memoryCache[$IP] = $host;
			return $host;
		}
	}
	public static function errorsOff(){
		self::$lastErrorReporting = @ini_get('error_reporting');
		@error_reporting(0);
		self::$lastDisplayErrors = @ini_get('display_errors');
		self::iniSet('display_errors', 0);
		if(class_exists('wfScan')){ wfScan::$errorHandlingOn = false; }
	}
	public static function errorsOn(){
		@error_reporting(self::$lastErrorReporting);
		self::iniSet('display_errors', self::$lastDisplayErrors);
		if(class_exists('wfScan')){ wfScan::$errorHandlingOn = true; }
	}
	//Note this function may report files that are too big which actually are not too big but are unseekable and throw an error on fseek(). But that's intentional
	public static function fileTooBig($file, &$size = false, &$handle = false){ //Deals with files > 2 gigs on 32 bit systems which are reported with the wrong size due to integer overflow
		if (!@is_file($file) || !@is_readable($file)) { return false; } //Only apply to readable files
		wfUtils::errorsOff();
		$fh = @fopen($file, 'rb');
		wfUtils::errorsOn();
		if(! $fh){ return false; }
		try {
			if(@fseek($fh, WORDFENCE_MAX_FILE_SIZE_OFFSET, SEEK_SET) === 0 && !empty(fread($fh, 1))){
				return true;
			} //Otherwise we couldn't seek there so it must be smaller
			if ($size !== false && @fseek($fh, 0, SEEK_END) === 0) {
				$size = @ftell($fh);
				if ($size === false)
					$size = 0; // Assume 0 if unable to determine file size
			}
			return false;
		} catch(Exception $e){
			return true; //If we get an error don't scan this file, report it's too big.
		} finally {
			if ($handle === false) {
				fclose($fh);
			}
			else {
				$handle = $fh;
			}
		}
	}
	public static function fileOver2Gigs($file){ //Surround calls to this func with try/catch because fseek may throw error.
		$fh = @fopen($file, 'rb');
		if(! $fh){ return false; }
		$offset = 2147483647;
		$tooBig = false;
		//My throw an error so surround calls to this func with try/catch
		if(@fseek($fh, $offset, SEEK_SET) === 0){
			if(strlen(fread($fh, 1)) === 1){
				$tooBig = true;
			}
		} //Otherwise we couldn't seek there so it must be smaller
		@fclose($fh);
		return $tooBig;
	}
	public static function countryCode2Name($code){
		require(dirname(__FILE__) . '/wfBulkCountries.php'); /** @var array $wfBulkCountries */
		if(isset($wfBulkCountries[$code])){
			return $wfBulkCountries[$code];
		} else {
			return '';
		}
	}
	public static function shouldDisplayRegion($country) {
		$countries_to_show_for = array('united states', 'canada', 'australia');
		return in_array(strtolower($country), $countries_to_show_for);
	}
	public static function extractBareURI($URL){
		$URL = preg_replace('/^https?:\/\/[^\/]+/i', '', $URL); //strip of method and host
		$URL = preg_replace('/\#.*$/', '', $URL); //strip off fragment
		$URL = preg_replace('/\?.*$/', '', $URL); //strip off query string
		return $URL;
	}
	public static function requireIpLocator() {
		/**
		 * This is also used in the WAF so in certain site setups (i.e. nested sites in subdirectories)
		 * it's possible for this to already have been loaded from a different installation of the
		 * plugin and hence require_once doesn't help as it's a different file path. There is no guarantee
		 * that the two plugin installations are the same version, so should the wfIpLocator class or any
		 * of its dependencies change in a manner that is not backwards compatible, this may need to be
		 * handled differently.
		 */
		if (!class_exists('wfIpLocator'))
			require_once(__DIR__ . '/wfIpLocator.php');
	}
	public static function IP2Country($ip){
		self::requireIpLocator();
		return wfIpLocator::getInstance()->getCountryCode($ip);
	}
	public static function geoIPVersion() {
		self::requireIpLocator();
		$version = wfIpLocator::getInstance()->getDatabaseVersion();
		return $version === null ? 0 : $version;
	}
	public static function siteURLRelative(){
		if(is_multisite()){
			$URL = network_site_url();
		} else {
			$URL = site_url();
		}
		$URL = preg_replace('/^https?:\/\/[^\/]+/i', '', $URL);
		$URL = rtrim($URL, '/') . '/';
		return $URL;
	}
	public static function localHumanDate(){
		return date('l jS \of F Y \a\t h:i:s A', time() + (3600 * get_option('gmt_offset')));
	}
	public static function localHumanDateShort(){
		return date('D jS F \@ h:i:sA', time() + (3600 * get_option('gmt_offset')));
	}
	public static function funcEnabled($func){
		if (!function_exists($func)){ return false; }
		if (!is_callable($func)) { return false; }
		$disabled = explode(',', ini_get('disable_functions'));
		if (in_array($func, $disabled)) {
			return false;
		}
		return true;
	}
	
	/**
	 * Parses a callable and returns its components or null if it's not a valid callable. This does not attempt to verify
	 * that the callable can actually be executed (e.g., the function may not exist), just that its structure is correct.
	 * 
	 * The array returned will have keys corresponding to the `wfUtils::CALLABLE_` constants.
	 * 
	 * @param callable $callable
	 * @return array|null
	 */
	public static function parseCallable($callable) {
		try {
			if (!@is_callable($callable, true, $parsed)) {
				return null;
			}
		}
		catch (Throwable $t) {
			return null;
		}
		
		$className = null;
		$functionName = null;
		$isClosure = false;
		$isInstance = false;
		$isInvokable = false;
		if (substr_count($parsed, '::')) {
			$components = explode('::', $parsed);
			$isClosure = $components[0] == 'Closure';
			if (!$isClosure) {
				$className = $components[0];
				$functionName = $components[1];
			}
		}
		else if (is_string($callable)) {
			$functionName = $parsed;
		}
		
		if (is_array($callable) && count($callable) == 2) {
			$isInstance = is_object($callable[0]);
		}
		else if (!$isClosure && is_object($callable)) {
			$isInvokable = true;
		}
		
		return array(
			self::CALLABLE_CLASS => $className,
			self::CALLABLE_FUNCTION => $functionName,
			self::CALLABLE_IS_CLOSURE => $isClosure,
			self::CALLABLE_IS_INSTANCE => $isInstance,
			self::CALLABLE_IS_INVOKABLE => $isInvokable,
		);
	}
	
	public static function iniSet($key, $val){
		if(self::funcEnabled('ini_set')){
			@ini_set($key, $val);
		}
	}
	public static function doNotCache(){
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must-revalidate, private, max-age=0");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); //In the past
		if(! defined('DONOTCACHEPAGE')){ define('DONOTCACHEPAGE', true); }
		if(! defined('DONOTCACHEDB')){ define('DONOTCACHEDB', true); }
		if(! defined('DONOTCDN')){ define('DONOTCDN', true); }
		if(! defined('DONOTCACHEOBJECT')){ define('DONOTCACHEOBJECT', true); }
		wfCache::doNotCache();
	}
	public static function isUABlocked($uaPattern){ // takes a pattern using asterisks as wildcards, turns it into regex and checks it against the visitor UA returning true if blocked
		return fnmatch($uaPattern, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', FNM_CASEFOLD);
	}
	public static function isRefererBlocked($refPattern){
		return fnmatch($refPattern, !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', FNM_CASEFOLD);
	}
	
	public static function error_clear_last() {
		if (function_exists('error_clear_last')) {
			error_clear_last();
		}
		else {
			// set error_get_last() to defined state by forcing an undefined variable error
			set_error_handler('wfUtils::_resetErrorsHandler', 0);
			@$undefinedVariable;
			restore_error_handler();
		}
	}
	
	/**
	 * Logs the error given or the last PHP error to our log, rate limiting if needed.
	 * 
	 * @param string $limiter_key
	 * @param string $label
	 * @param null|string $error The error to log. If null, it will be the result of error_get_last
	 * @param int $rate Logging will only occur once per $rate seconds.
	 */
	public static function check_and_log_last_error($limiter_key, $label, $error = null, $rate = 3600 /* 1 hour */) {
		if ($error === null) {
			$error = error_get_last();
			if ($error === null) {
				return;
			}
			else if ($error['file'] === __FILE__) {
				return;
			}
			$error = $error['message'];
		}
		
		$rateKey = 'lastError_rate_' . $limiter_key;
		$previousKey = 'lastError_prev_' . $limiter_key;
		$previousError = wfConfig::getJSON($previousKey, array(0, false));
		if ($previousError[1] != $error) {
			if (wfConfig::getInt($rateKey) < time() - $rate) {
				wfConfig::set($rateKey, time());
				wfConfig::setJSON($previousKey, array(time(), $error));
				wordfence::status(2, 'error', $label . ' ' . $error);
			}
		}
	}
	
	public static function last_error($limiter_key) {
		$previousKey = 'lastError_prev_' . $limiter_key;
		$previousError = wfConfig::getJSON($previousKey, array(0, false));
		if ($previousError[1]) {
			return wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), $previousError[0]) . ': ' . $previousError[1];
		}
		return false;
	}
	
	public static function _resetErrorsHandler($errno, $errstr, $errfile, $errline) {
		//Do nothing
	}

	/**
	 * @param $startIP
	 * @param $endIP
	 * @return array
	 */
	public static function rangeToCIDRs($startIP, $endIP){
		$start_ip_printable = wfUtils::inet_ntop($startIP);
		if (filter_var($start_ip_printable, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return self::rangeToCIDRsIPv4(current(unpack('N', substr($startIP, 12, 4))), current(unpack('N', substr($endIP, 12, 4))));
		}
		$startIPBin = str_pad(wfHelperBin::bin2str($startIP), 128, '0', STR_PAD_LEFT);
		$endIPBin = str_pad(wfHelperBin::bin2str($endIP), 128, '0', STR_PAD_LEFT);
		$IPIncBin = $startIPBin;
		$CIDRs = array();
		while (strcmp($IPIncBin, $endIPBin) <= 0) {
			$longNetwork = 128;
			$IPNetBin = $IPIncBin;
			while (($IPIncBin[$longNetwork - 1] == '0') && (strcmp(substr_replace($IPNetBin, '1', $longNetwork - 1, 1), $endIPBin) <= 0)) {
				$IPNetBin[$longNetwork - 1] = '1';
				$longNetwork--;
			}
			$CIDRs[] = self::inet_ntop(str_pad(wfHelperBin::str2bin($IPIncBin), 16, "\x00", STR_PAD_LEFT)) . ($longNetwork < 128 ? '/' . $longNetwork : '');
			$IPIncBin = str_pad(wfHelperBin::bin2str(wfHelperBin::addbin2bin(chr(1), wfHelperBin::str2bin($IPNetBin))), 128, '0', STR_PAD_LEFT);
		}
		return $CIDRs;
	}

	public static function rangeToCIDRsIPv4($startIP, $endIP){
		$startIPBin = sprintf('%032b', $startIP);
		$endIPBin = sprintf('%032b', $endIP);
		$IPIncBin = $startIPBin;
		$CIDRs = array();
		while(strcmp($IPIncBin, $endIPBin) <= 0){
			$longNetwork = 32;
			$IPNetBin = $IPIncBin;
			while(($IPIncBin[$longNetwork - 1] == '0') && (strcmp(substr_replace($IPNetBin, '1', $longNetwork - 1, 1), $endIPBin) <= 0)){
				$IPNetBin[$longNetwork - 1] = '1';
				$longNetwork--;
			}
			$CIDRs[] = long2ip(bindec($IPIncBin)) . ($longNetwork < 32 ? '/' . $longNetwork : '');
			$IPIncBin = sprintf('%032b', bindec($IPNetBin) + 1);
		}
		return $CIDRs;
	}
	
	/**
	 * This is a convenience function for sending a JSON response and ensuring that execution stops after sending
	 * since wp_die() can be interrupted.
	 * 
	 * @param $response
	 * @param int|null $status_code
	 */
	public static function send_json($response, $status_code = null) {
		wp_send_json($response, $status_code);
		die();
	}

	public static function setcookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httpOnly = false) {
		if (empty($path)) { $path = COOKIEPATH; }
		if (empty($domain)) { $domain = COOKIE_DOMAIN; }
		if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
			setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
		}
		else {
			setcookie($name, $value, $expire, $path);
		}
	}
	public static function isNginx(){
		$sapi = php_sapi_name();
		$serverSoft = $_SERVER['SERVER_SOFTWARE'];
		if($sapi == 'fpm-fcgi' && stripos($serverSoft, 'nginx') !== false){
			return true;
		}
	}
	public static function getLastError(){
		$err = error_get_last();
		if(is_array($err)){
			return $err['message'];
		}
		return '';
	}
	public static function hostNotExcludedFromProxy($url){
		if(! defined('WP_PROXY_BYPASS_HOSTS')){
			return true; //No hosts are excluded
		}
		$hosts = explode(',', WP_PROXY_BYPASS_HOSTS);
		$url = preg_replace('/^https?:\/\//i', '', $url);
		$url = preg_replace('/\/.*$/', '', $url);
		$url = strtolower($url);
		foreach($hosts as $h){
			if(strtolower(trim($h)) == $url){
				return false;
			}
		}
		return true;
	}
	public static function hasXSS($URL){
		if(! preg_match('/^https?:\/\/[a-z0-9\.\-]+\/[^\':<>\"\\\]*$/i', $URL)){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $host
	 * @return array
	 */
	public static function resolveDomainName($host, $ipVersion = null) {
		if (!function_exists('dns_get_record')) {
			if ($ipVersion === 4 || $ipVersion === null) {
				$ips = gethostbynamel($host);
				if ($ips !== false)
					return $ips;
			}
			return array();
		}
		$recordTypes = array();
		if ($ipVersion === 4 || $ipVersion === null)
			$recordTypes[DNS_A] = 'ip';
		if ($ipVersion === 6 || $ipVersion === null)
			$recordTypes[DNS_AAAA] = 'ipv6';
		$ips = array();
		foreach ($recordTypes as $type => $key) {
			$records = @dns_get_record($host, $type);
			if ($records !== false && is_array($records)) {
				foreach ($records as $record) {
					if (array_key_exists($key, $record)) {
						$ips[] = $record[$key];
					}
				}
			}
		}
		return $ips;
	}

	/**
	 * Expand a compressed printable representation of an IPv6 address.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function expandIPv6Address($ip) {
		$hex = bin2hex(self::inet_pton($ip));
		$ip = substr(preg_replace("/([a-f0-9]{4})/i", "$1:", $hex), 0, -1);
		return $ip;
	}

	public static function set_html_content_type() {
		return 'text/html';
	}

	public static function htmlEmail($to, $subject, $body) {
		add_filter( 'wp_mail_content_type', 'wfUtils::set_html_content_type' );
		$result = wp_mail($to, $subject, $body);
		remove_filter( 'wp_mail_content_type', 'wfUtils::set_html_content_type' );
		return $result;
	}
	
	/**
	 * Convenience method to send an email via `wp_mail` and avoid an exception of another plugin overrides the call
	 * and throws one (Core does not currently throw any).
	 *
	 * @param $to
	 * @param $subject
	 * @param $message
	 * @param $headers
	 * @param $attachments
	 * @return bool
	 */
	public static function maybe_wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
		try {
			return wp_mail($to, $subject, $message, $headers, $attachments);
		}
		catch (Exception $e) {
			wordfence::status(2, 'error', 'Wordfence failed to send email: ' . $e->getMessage());
		}
		catch (Throwable $t) {
			wordfence::status(2, 'error', 'Wordfence failed to send email: ' . $t->getMessage());
		}
		return false;
	}

	/**
	 * @param string $readmePath
	 * @return bool
	 */
	public static function hideReadme($readmePath = null) {
		if ($readmePath === null) {
			$readmePath = ABSPATH . 'readme.html';
		}

		if (file_exists($readmePath)) {
			$readmePathInfo = pathinfo($readmePath);
			require_once(ABSPATH . WPINC . '/pluggable.php');
			$hiddenReadmeFile = $readmePathInfo['filename'] . '.' . wp_hash('readme') . '.' . $readmePathInfo['extension'];
			return @rename($readmePath, $readmePathInfo['dirname'] . '/' . $hiddenReadmeFile);
		}
		return false;
	}

	/**
	 * @param string $readmePath
	 * @return bool
	 */
	public static function showReadme($readmePath = null) {
		if ($readmePath === null) {
			$readmePath = ABSPATH . 'readme.html';
		}
		$readmePathInfo = pathinfo($readmePath);
		require_once(ABSPATH . WPINC . '/pluggable.php');
		$hiddenReadmeFile = $readmePathInfo['dirname'] . '/' . $readmePathInfo['filename'] . '.' . wp_hash('readme') . '.' . $readmePathInfo['extension'];
		if (file_exists($hiddenReadmeFile)) {
			return @rename($hiddenReadmeFile, $readmePath);
		}
		return false;
	}

	public static function htaccessAppend($code)
	{
		$htaccess = wfCache::getHtaccessPath();
		$content  = self::htaccess();
		if (wfUtils::isNginx() || !is_writable($htaccess)) {
			return false;
		}

		if (strpos($content, $code) === false) {
			// make sure we write this once
			file_put_contents($htaccess, $content . "\n" . trim($code), LOCK_EX);
		}

		return true;
	}
	
	public static function htaccessPrepend($code)
	{
		$htaccess = wfCache::getHtaccessPath();
		$content  = self::htaccess();
		if (wfUtils::isNginx() || !is_writable($htaccess)) {
			return false;
		}
		
		if (strpos($content, $code) === false) {
			// make sure we write this once
			file_put_contents($htaccess, trim($code) . "\n" . $content, LOCK_EX);
		}
		
		return true;
	}

	public static function htaccess() {
		$htaccess = wfCache::getHtaccessPath();
		if (is_readable($htaccess) && !wfUtils::isNginx()) {
			return file_get_contents($htaccess);
		}
		return "";
	}

	/**
	 * @param array $array
	 * @param mixed $oldKey
	 * @param mixed $newKey
	 * @return array
	 * @throws Exception
	 */
	public static function arrayReplaceKey($array, $oldKey, $newKey) {
		$keys = array_keys($array);
		if (($index = array_search($oldKey, $keys)) === false) {
			throw new Exception(sprintf('Key "%s" does not exist', $oldKey));
		}
		$keys[$index] = $newKey;
		return array_combine($keys, array_values($array));
	}
	
	/**
	 * Takes a string that may have characters that will be interpreted as invalid UTF-8 byte sequences and translates them into a string of the equivalent hex sequence.
	 * 
	 * @param $string
	 * @param bool $inline
	 * @return string
	 */
	public static function potentialBinaryStringToHTML($string, $inline = false, $allowmb4 = false) {
		$output = '';
		
		if (!defined('ENT_SUBSTITUTE')) {
			define('ENT_SUBSTITUTE', 0);
		}
		
		$span = '<span class="wf-hex-sequence">';
		if ($inline) {
			$span = '<span style="color:#587ECB">';
		}
		
		for ($i = 0; $i < wfUtils::strlen($string); $i++) {
			$c = $string[$i];
			$b = ord($c);
			if ($b < 0x20) {
				$output .= $span . '\x' . str_pad(dechex($b), 2, '0', STR_PAD_LEFT) . '</span>';
			}
			else if ($b < 0x80) {
				$output .= htmlspecialchars($c, ENT_QUOTES, 'ISO-8859-1');
			}
			else { //Assume multi-byte UTF-8
				$bytes = 0;
				$test = $b;
				
				while (($test & 0x80) > 0) {
					$bytes++;
					$test = (($test << 1) & 0xff);
				}
				
				$brokenUTF8 = ($i + $bytes > wfUtils::strlen($string) || $bytes == 1);
				if (!$brokenUTF8) { //Make sure we have all the bytes
					for ($n = 1; $n < $bytes; $n++) {
						$c2 = $string[$i + $n];
						$b2 = ord($c2);
						if (($b2 & 0xc0) != 0x80) {
							$brokenUTF8 = true;
							$bytes = $n;
							break;
						}
					}
				}
				
				if (!$brokenUTF8) { //Ensure the byte sequences are within the accepted ranges: https://tools.ietf.org/html/rfc3629
					/*
					 * UTF8-octets = *( UTF8-char )
   					 * UTF8-char   = UTF8-1 / UTF8-2 / UTF8-3 / UTF8-4
   					 * UTF8-1      = %x00-7F
   					 * UTF8-2      = %xC2-DF UTF8-tail
   					 * UTF8-3      = %xE0 %xA0-BF UTF8-tail / %xE1-EC 2( UTF8-tail ) /
   					 *               %xED %x80-9F UTF8-tail / %xEE-EF 2( UTF8-tail )
   					 * UTF8-4      = %xF0 %x90-BF 2( UTF8-tail ) / %xF1-F3 3( UTF8-tail ) /
   					 *               %xF4 %x80-8F 2( UTF8-tail )
   					 * UTF8-tail   = %x80-BF
					 */
					
					$testString = wfUtils::substr($string, $i, $bytes);
					$regex = '/^(?:' .
						'[\xc2-\xdf][\x80-\xbf]' . //UTF8-2
						'|' . '\xe0[\xa0-\xbf][\x80-\xbf]' . //UTF8-3
						'|' . '[\xe1-\xec][\x80-\xbf]{2}' .
						'|' . '\xed[\x80-\x9f][\x80-\xbf]' .
						'|' . '[\xee-\xef][\x80-\xbf]{2}';
					if ($allowmb4) {
						$regex .= '|' . '\xf0[\x90-\xbf][\x80-\xbf]{2}' . //UTF8-4
							'|' . '[\xf1-\xf3][\x80-\xbf]{3}' .
							'|' . '\xf4[\x80-\x8f][\x80-\xbf]{2}';
					}
					$regex  .= ')$/';
					if (!preg_match($regex, $testString)) {
						$brokenUTF8 = true;
					}
				}
				
				if ($brokenUTF8) {
					$bytes = min($bytes, strlen($string) - $i);
					for ($n = 0; $n < $bytes; $n++) {
						$c2 = $string[$i + $n];
						$b2 = ord($c2);
						$output .= $span . '\x' . str_pad(dechex($b2), 2, '0', STR_PAD_LEFT) . '</span>';
					}
					$i += ($bytes - 1);
				}
				else {
					$output .= htmlspecialchars(wfUtils::substr($string, $i, $bytes), ENT_QUOTES | ENT_SUBSTITUTE, 'ISO-8859-1');
					$i += ($bytes - 1);
				}
			}
		}
		return $output;
	}
	
	/**
	 * A custom version of `esc_attr` that allows the escaping behavior to be customized
	 *
	 * @param string $text
	 * @param int $quote_style
	 * @param string $charset
	 * @param bool $double_encode
	 *
	 * @return string
	 */
	public static function esc_attr( $text, $quote_style = ENT_QUOTES, $charset = 'UTF-8', $double_encode = false ) {
		$original = $text;
		$text = wp_check_invalid_utf8((string) $text);
		
		if (0 === strlen($text)) { return ''; }
		else if (!preg_match( '/[&<>"\']/', $text ) ) { return $text; }
		
		if (!in_array($quote_style, array(ENT_NOQUOTES, ENT_COMPAT, ENT_QUOTES, 'single', 'double'), true)) {
			$quote_style = ENT_QUOTES;
		}
		
		$_quote_style = $quote_style;
		
		if ('double' === $quote_style) {
			$quote_style  = ENT_COMPAT;
			$_quote_style = ENT_COMPAT;
		}
		else if ('single' === $quote_style) {
			$quote_style = ENT_NOQUOTES;
		}
		
		if (!$double_encode) {
			$text = wp_kses_normalize_entities($text, ($quote_style & ENT_XML1) ? 'xml' : 'html');
		}
		
		$text = htmlspecialchars($text, $quote_style, $charset, $double_encode);
		
		if ('single' === $_quote_style) {
			$text = str_replace( "'", '&#039;', $text );
		}
		
		return apply_filters('attribute_escape', $text, $original);
	}
	
	public static function requestDetectProxyCallback($timeout = 2, $blocking = true, $forceCheck = false) {
		$currentRecommendation = wfConfig::get('detectProxyRecommendation', '');
		if (!$forceCheck) {
			$detectProxyNextCheck = wfConfig::get('detectProxyNextCheck', false);
			if ($detectProxyNextCheck !== false && time() < $detectProxyNextCheck) {
				if (empty($currentRecommendation)) {
					wfConfig::set('detectProxyRecommendation', 'DEFERRED', wfConfig::DONT_AUTOLOAD);
				}
				return; //Let it pull the currently-stored value
			}
		}

		try {
			$waf = wfWAF::getInstance();
			if ($waf->getStorageEngine()->getConfig('attackDataKey', false) === false) {
				$waf->getStorageEngine()->setConfig('attackDataKey', mt_rand(0, 0xfff));
			}
			$response = wp_remote_get(sprintf(WFWAF_API_URL_SEC . "proxy-check/%d.txt", $waf->getStorageEngine()->getConfig('attackDataKey')), array('headers' => array('Referer' => false)));
			
			if (!is_wp_error($response)) {
				$okToSendBody = wp_remote_retrieve_body($response);
				if (preg_match('/^(ok|wait),\s*(\d+)$/i', $okToSendBody, $matches)) {
					$command = $matches[1];
					$ttl = $matches[2];
					if ($command == 'wait') {
						wfConfig::set('detectProxyNextCheck', time() + $ttl, wfConfig::DONT_AUTOLOAD);
						if (empty($currentRecommendation) || $currentRecommendation == 'UNKNOWN') {
							wfConfig::set('detectProxyRecommendation', 'DEFERRED', wfConfig::DONT_AUTOLOAD);
						}
						return;
					}
					
					wfConfig::set('detectProxyNextCheck', time() + $ttl, wfConfig::DONT_AUTOLOAD);
				}
				else { //Unknown response
					wfConfig::set('detectProxyNextCheck', false, wfConfig::DONT_AUTOLOAD);
					if (empty($currentRecommendation) || $currentRecommendation == 'UNKNOWN') {
						wfConfig::set('detectProxyRecommendation', 'DEFERRED', wfConfig::DONT_AUTOLOAD);
					}
					return;
				}
			}
		}
		catch (Exception $e) {
			return;
		}
		
		$nonce = bin2hex(wfWAFUtils::random_bytes(32));
		$callback = self::getSiteBaseURL() . '?_wfsf=detectProxy';
		
		wfConfig::set('detectProxyNonce', $nonce, wfConfig::DONT_AUTOLOAD);
		wfConfig::set('detectProxyRecommendation', '', wfConfig::DONT_AUTOLOAD);
		
		$payload = array(
			'nonce' => $nonce,
			'callback' => $callback,
		);
		
		$homeurl = wfUtils::wpHomeURL();
		$siteurl = wfUtils::wpSiteURL();
		
		try {
			$response = wp_remote_post(WFWAF_API_URL_SEC . "?" . http_build_query(array(
					'action' => 'detect_proxy',
					'k'      => wfConfig::get('apiKey'),
					's'      => $siteurl,
					'h'		 => $homeurl,
					't'		 => microtime(true),
					'lang'   => get_site_option('WPLANG'),
				), '', '&'),
				array(
					'body'    => json_encode($payload),
					'headers' => array(
						'Content-Type' => 'application/json',
						'Referer' => false,
					),
					'timeout' => $timeout,
					'blocking' => $blocking,
				));
		
			if (!is_wp_error($response)) {
				$jsonResponse = wp_remote_retrieve_body($response);
				$decoded = @json_decode($jsonResponse, true);
				if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['ip']) && wfUtils::isValidIP($decoded['data']['ip'])) {
					wfConfig::set('serverIP', time() . ';' . $decoded['data']['ip']);
				}
			}
		}
		catch (Exception $e) {
			return;
		}
	}
	
	/**
	 * @return bool Returns false if the payload is invalid, true if it processed the callback (even if the IP wasn't found).
	 */
	public static function processDetectProxyCallback() {
		$nonce = wfConfig::get('detectProxyNonce', '');
		$testNonce = (isset($_POST['nonce']) ? $_POST['nonce'] : '');
		if (empty($nonce) || empty($testNonce)) {
			return false;
		}
		
		if (!hash_equals($nonce, $testNonce)) {
			return false;
		}
		
		$ips = (isset($_POST['ips']) ? $_POST['ips'] : array());
		if (empty($ips)) {
			return false;
		}
		
		$expandedIPs = array();
		foreach ($ips as $ip) {
			$expandedIPs[] = self::inet_pton($ip);
		}
		
		$checks = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR');
		foreach ($checks as $key) {
			if (!isset($_SERVER[$key])) {
				continue;
			}
			
			$testIP = self::getCleanIPAndServerVar(array(array($_SERVER[$key], $key)));
			if ($testIP === false) {
				continue;
			}
			
			$testIP = self::inet_pton($testIP[0]);
			if (in_array($testIP, $expandedIPs)) {
				wfConfig::set('detectProxyRecommendation', $key, wfConfig::DONT_AUTOLOAD);
				wfConfig::set('detectProxyNonce', '', wfConfig::DONT_AUTOLOAD);
				return true;
			}
		}
		
		wfConfig::set('detectProxyRecommendation', 'UNKNOWN', wfConfig::DONT_AUTOLOAD);
		wfConfig::set('detectProxyNonce', '', wfConfig::DONT_AUTOLOAD);
		return true;
	}
	
	/**
	 * Returns a v4 UUID.
	 * 
	 * @return string
	 */
	public static function uuid() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			wfWAFUtils::random_int(0, 0xffff), wfWAFUtils::random_int(0, 0xffff),
			
			// 16 bits for "time_mid"
			wfWAFUtils::random_int(0, 0xffff),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			wfWAFUtils::random_int(0, 0x0fff) | 0x4000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			wfWAFUtils::random_int(0, 0x3fff) | 0x8000,
			
			// 48 bits for "node"
			wfWAFUtils::random_int(0, 0xffff), wfWAFUtils::random_int(0, 0xffff), wfWAFUtils::random_int(0, 0xffff)
		);
	}
	
	public static function base32_encode($rawString, $rightPadFinalBits = false, $padFinalGroup = false, $padCharacter = '=') //Adapted from https://github.com/ademarre/binary-to-text-php
	{
		// Unpack string into an array of bytes
		$bytes = unpack('C*', $rawString);
		$byteCount = count($bytes);
		
		$encodedString = '';
		$byte = array_shift($bytes);
		$bitsRead = 0;
		$oldBits = 0;
		
		$chars             = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bitsPerCharacter  = 5;
		
		$charsPerByte = 8 / $bitsPerCharacter;
		$encodedLength = $byteCount * $charsPerByte;
		
		// Generate encoded output; each loop produces one encoded character
		for ($c = 0; $c < $encodedLength; $c++) {
			
			// Get the bits needed for this encoded character
			if ($bitsRead + $bitsPerCharacter > 8) {
				// Not enough bits remain in this byte for the current character
				// Save the remaining bits before getting the next byte
				$oldBitCount = 8 - $bitsRead;
				$oldBits = $byte ^ ($byte >> $oldBitCount << $oldBitCount);
				$newBitCount = $bitsPerCharacter - $oldBitCount;
				
				if (!$bytes) {
					// Last bits; match final character and exit loop
					if ($rightPadFinalBits) $oldBits <<= $newBitCount;
					$encodedString .= $chars[$oldBits];
					
					if ($padFinalGroup) {
						// Array of the lowest common multiples of $bitsPerCharacter and 8, divided by 8
						$lcmMap = array(1 => 1, 2 => 1, 3 => 3, 4 => 1, 5 => 5, 6 => 3, 7 => 7, 8 => 1);
						$bytesPerGroup = $lcmMap[$bitsPerCharacter];
						$pads = $bytesPerGroup * $charsPerByte - ceil((strlen($rawString) % $bytesPerGroup) * $charsPerByte);
						$encodedString .= str_repeat($padCharacter, $pads);
					}
					
					break;
				}
				
				// Get next byte
				$byte = array_shift($bytes);
				$bitsRead = 0;
				
			} else {
				$oldBitCount = 0;
				$newBitCount = $bitsPerCharacter;
			}
			
			// Read only the needed bits from this byte
			$bits = $byte >> 8 - ($bitsRead + ($newBitCount));
			$bits ^= $bits >> $newBitCount << $newBitCount;
			$bitsRead += $newBitCount;
			
			if ($oldBitCount) {
				// Bits come from seperate bytes, add $oldBits to $bits
				$bits = ($oldBits << $newBitCount) | $bits;
			}
			
			$encodedString .= $chars[$bits];
		}
		
		return $encodedString;
	}
	
	private static function _home_url_nofilter($path = '', $scheme = null) { //A version of the native get_home_url and get_option without the filter calls
		global $pagenow, $wpdb, $blog_id;
		
		static $cached_url = null;
		if ($cached_url !== null) {
			return $cached_url;
		}
		
		if (defined('WP_HOME') && WORDFENCE_PREFER_WP_HOME_FOR_WPML) {
			$cached_url = WP_HOME;
			return $cached_url;
		}
		
		if ( empty( $blog_id ) || !is_multisite() ) {
			$url = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'home' LIMIT 1");
			if (empty($url)) { //get_option uses siteurl instead if home is empty
				$url = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl' LIMIT 1");
			}
		}
		else if (is_multisite()) {
			$current_network = get_network();
			if ( 'relative' == $scheme )
				$url = rtrim($current_network->path, '/');
			else
				$url = 'http://' . rtrim($current_network->domain, '/') . '/' . trim($current_network->path, '/');
		}
		
		if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) ) {
			if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow )
				$scheme = 'https';
			else
				$scheme = parse_url( $url, PHP_URL_SCHEME );
		}
		
		$url = set_url_scheme( $url, $scheme );
		
		if ( $path && is_string( $path ) )
			$url .= '/' . ltrim( $path, '/' );
		
		$cached_url = $url;
		return $url;
	}
	
	public static function refreshCachedHomeURL() {
		$pullDirectly = class_exists('WPML_URL_Filters');
		$homeurl = '';
		if ($pullDirectly) {
			//A version of the native get_home_url without the filter call
			$homeurl = self::_home_url_nofilter();
		}
		
		if (function_exists('get_bloginfo') && empty($homeurl)) {
			if (is_multisite()) {
				$homeurl = network_home_url();
			}
			else {
				$homeurl = home_url();
			}
			
			$homeurl = rtrim($homeurl, '/'); //Because previously we used get_bloginfo and it returns http://example.com without a '/' char.
		}
		
		if (wfConfig::get('wp_home_url') !== $homeurl) {
			wfConfig::set('wp_home_url', $homeurl);
		}
	}
	
	public static function wpHomeURL($path = '', $scheme = null) {
		$homeurl = wfConfig::get('wp_home_url', '');
		if (function_exists('get_bloginfo') && empty($homeurl)) {
			if (is_multisite()) {
				$homeurl = network_home_url($path, $scheme);
			}
			else {
				$homeurl = home_url($path, $scheme);
			}
			
			$homeurl = rtrim($homeurl, '/'); //Because previously we used get_bloginfo and it returns http://example.com without a '/' char.
		}
		else {
			$homeurl = set_url_scheme($homeurl, $scheme);
			if ($path && is_string($path)) {
				$homeurl .= '/' . ltrim($path, '/');
			}
		}
		return $homeurl;
	}
	
	private static function _site_url_nofilter($path = '', $scheme = null) { //A version of the native get_site_url and get_option without the filter calls
		global $pagenow, $wpdb, $blog_id;
		
		static $cached_url = null;
		if ($cached_url !== null) {
			return $cached_url;
		}
		
		if (defined('WP_SITEURL') && WORDFENCE_PREFER_WP_HOME_FOR_WPML) {
			$cached_url = WP_SITEURL;
			return $cached_url;
		}
		
		if ( empty( $blog_id ) || !is_multisite() ) {
			$url = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl' LIMIT 1");
		}
		else if (is_multisite()) {
			$current_network = get_network();
			if ( 'relative' == $scheme )
				$url = rtrim($current_network->path, '/');
			else
				$url = 'http://' . rtrim($current_network->domain, '/') . '/' . trim($current_network->path, '/');
		}
		
		if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) ) {
			if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow )
				$scheme = 'https';
			else
				$scheme = parse_url( $url, PHP_URL_SCHEME );
		}
		
		$url = set_url_scheme( $url, $scheme );
		
		if ( $path && is_string( $path ) )
			$url .= '/' . ltrim( $path, '/' );
		
		$cached_url = $url;
		return $url;
	}
	
	public static function refreshCachedSiteURL() {
		$pullDirectly = class_exists('WPML_URL_Filters');
		$siteurl = '';
		if ($pullDirectly) {
			//A version of the native get_home_url without the filter call
			$siteurl = self::_site_url_nofilter();
		}
		
		if (function_exists('get_bloginfo') && empty($siteurl)) {
			if (is_multisite()) {
				$siteurl = network_site_url();
			}
			else {
				$siteurl = site_url();
			}
			
			$siteurl = rtrim($siteurl, '/'); //Because previously we used get_bloginfo and it returns http://example.com without a '/' char.
		}
		
		if (wfConfig::get('wp_site_url') !== $siteurl) {
			wfConfig::set('wp_site_url', $siteurl);
		}
	}
	
	/**
	 * Equivalent to network_site_url but uses the cached value for the URL if we have it
	 * to avoid breaking on sites that define it based on the requesting hostname.
	 * 
	 * @param string $path
	 * @param null|string $scheme
	 * @return string
	 */
	public static function wpSiteURL($path = '', $scheme = null) {
		$siteurl = wfConfig::get('wp_site_url', '');
		if (function_exists('get_bloginfo') && empty($siteurl)) {
			if (is_multisite()) {
				$siteurl = network_site_url($path, $scheme);
			}
			else {
				$siteurl = site_url($path, $scheme);
			}
			
			$siteurl = rtrim($siteurl, '/'); //Because previously we used get_bloginfo and it returns http://example.com without a '/' char.
		}
		else {
			$siteurl = set_url_scheme($siteurl, $scheme);
			if ($path && is_string($path)) {
				$siteurl .= '/' . ltrim($path, '/');
			}
		}
		return $siteurl;
	}
	
	/**
	 * Equivalent to network_admin_url but uses the cached value for the URL if we have it
	 * to avoid breaking on sites that define it based on the requesting hostname.
	 *
	 * @param string $path
	 * @param null|string $scheme
	 * @return string
	 */
	public static function wpAdminURL($path = '', $scheme = null) {
		if (!is_multisite()) {
			$adminURL = self::wpSiteURL('wp-admin/', $scheme);
		}
		else {
			$adminURL = self::wpSiteURL('wp-admin/network/', $scheme);
		}
		
		if ($path && is_string($path)) {
			$adminURL .= ltrim($path, '/');
		}
		
		if (!is_multisite()) {
			return apply_filters('admin_url', $adminURL, $path, null);
		}
		
		return apply_filters('network_admin_url', $adminURL, $path);
	}
	
	public static function wafInstallationType() {
		$storage = 'file';
		if (defined('WFWAF_STORAGE_ENGINE')) { $storage = WFWAF_STORAGE_ENGINE; }
		
		try {
			$status = (defined('WFWAF_ENABLED') && !WFWAF_ENABLED) ? 'disabled' : wfWaf::getInstance()->getStorageEngine()->getConfig('wafStatus');
			if (defined('WFWAF_ENABLED') && !WFWAF_ENABLED) {
				return "{$status}|const|{$storage}";
			}
			else if (defined('WFWAF_SUBDIRECTORY_INSTALL') && WFWAF_SUBDIRECTORY_INSTALL) {
				return "{$status}|subdir|{$storage}";
			}
			else if (defined('WFWAF_AUTO_PREPEND') && WFWAF_AUTO_PREPEND) {
				return "{$status}|extended|{$storage}";
			}
			
			return "{$status}|basic|{$storage}";
		}
		catch (Exception $e) {
			//Do nothing
		}
		
		return 'unknown';
	}
	
	public static function hex2bin($string) { //Polyfill for PHP < 5.4
		if (!is_string($string)) { return false; }
		if (strlen($string) % 2 == 1) { return false; }
		return pack('H*', $string);
	}
	
	/**
	 * Returns whether or not the site should be treated as if it's full-time SSL.
	 * 
	 * @return bool
	 */
	public static function isFullSSL() {
		return is_ssl() && parse_url(self::wpHomeURL(), PHP_URL_SCHEME) === 'https'; //It's possible for only wp-admin to be SSL so we check the home URL too
	}

	/**
	 * Identical to the same functions in wfWAFUtils.
	 * 
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
		return self::callMBSafeStrFunction('strpos', $args);
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
			$haystack, $needle, $offset, $length
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
	
	public static function sets_equal($a1, $a2) {
		if (!is_array($a1) || !is_array($a2)) {
			return false;
		}
		
		if (count($a1) != count($a2)) {
			return false;
		}
		
		sort($a1, SORT_NUMERIC);
		sort($a2, SORT_NUMERIC);
		return $a1 == $a2;
	}
	
	/**
	 * Returns true if $maybeSubset is contained within $set.
	 *
	 * @param array $set
	 * @param array $maybeSubset
	 * @return bool
	 */
	public static function is_subset($set, $maybeSubset) {
		if (!is_array($set) || !is_array($maybeSubset)) {
			return false;
		}
		return count(array_intersect($set, $maybeSubset)) == count($maybeSubset);
	}
	
	public static function array_first($array) {
		if (empty($array)) {
			return null;
		}
		
		$values = array_values($array);
		return $values[0];
	}
	
	public static function array_last($array) {
		if (empty($array)) {
			return null;
		}
		
		$values = array_values($array);
		return $values[count($values) - 1];
	}
	
	public static function array_strtolower($array) {
		return array_map(function($v) { return self::strtolower($v); }, $array);
	}
	
	public static function array_column($input = null, $columnKey = null, $indexKey = null) { //Polyfill from https://github.com/ramsey/array_column/blob/master/src/array_column.php
		$argc = func_num_args();
		$params = func_get_args();
		if ($argc < 2) {
			trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
			return null;
		}
		
		if (!is_array($params[0])) {
			trigger_error(
				'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
				E_USER_WARNING
			);
			return null;
		}
		
		if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null && !(is_object($params[1]) && method_exists($params[1], '__toString'))) {
			trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
			return false;
		}
		
		if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2]) && !(is_object($params[2]) && method_exists($params[2], '__toString'))) {
			trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
			return false;
		}
		
		$paramsInput = $params[0];
		$paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
		$paramsIndexKey = null;
		if (isset($params[2])) {
			if (is_float($params[2]) || is_int($params[2])) {
				$paramsIndexKey = (int) $params[2];
			}
			else {
				$paramsIndexKey = (string) $params[2];
			}
		}
		
		$resultArray = array();
		foreach ($paramsInput as $row) {
			$key = $value = null;
			$keySet = $valueSet = false;
			if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
				$keySet = true;
				$key = (string) $row[$paramsIndexKey];
			}
			
			if ($paramsColumnKey === null) {
				$valueSet = true;
				$value = $row;
			}
			elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
				$valueSet = true;
				$value = $row[$paramsColumnKey];
			}
			
			if ($valueSet) {
				if ($keySet) {
					$resultArray[$key] = $value;
				}
				else {
					$resultArray[] = $value;
				}
			}
		}
		
		return $resultArray;
	}
	
	/**
	 * Returns $string if it isn't empty, $ifEmpty if it is.
	 * 
	 * @param string $string
	 * @param string $ifEmpty
	 * @return string
	 */
	public static function string_empty($string, $ifEmpty) {
		if (empty($string)) {
			return $ifEmpty;
		}
		return $string;
	}
	
	/**
	 * Returns the current timestamp, adjusted as needed to get close to what we consider a true timestamp. We use this
	 * because a significant number of servers are using a drastically incorrect time.
	 *
	 * @return int
	 */
	public static function normalizedTime($base = false) {
		if ($base === false) {
			$base = time();
		}
		
		$offset = (int) wfConfig::get('timeoffset_wf', 0);
		return $base + $offset;
	}
	
	/**
	 * Returns what we consider a true timestamp, adjusted as needed to match the local server's drift. We use this
	 * because a significant number of servers are using a drastically incorrect time.
	 *
	 * @return int
	 */
	public static function denormalizedTime($base) {
		$offset = (int) wfConfig::get('timeoffset_wf', 0);
		return $base - $offset;
	}
	
	/**
	 * Returns the number of minutes for the time zone offset from UTC. If $timestamp and using a named time zone, 
	 * it will be adjusted automatically to match whether or not the server's time zone is in Daylight Savings Time.
	 * 
	 * @param bool|int $timestamp Assumed to be in UTC. If false, defaults to the current timestamp.
	 * @return int
	 */
	public static function timeZoneMinutes($timestamp = false) {
		if ($timestamp === false) {
			$timestamp = time();
		}
		
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$timezone = new DateTimeZone($tz);
			$dtStr = gmdate("c", (int) $timestamp); //Have to do it this way because of PHP 5.2
			$dt = new DateTime($dtStr, $timezone);
			return (int) ($timezone->getOffset($dt) / 60);
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				return (int) ($gmt * 60);
			}
		}
		
		return 0;
	}
	
	/**
	 * Formats and returns the given timestamp using the time zone set for the WordPress installation.
	 * 
	 * @param string $format See the PHP docs on DateTime for the format options. 
	 * @param int|bool $timestamp Assumed to be in UTC. If false, defaults to the current timestamp.
	 * @return string
	 */
	public static function formatLocalTime($format, $timestamp = false) {
		if ($timestamp === false) {
			$timestamp = time();
		}
		
		$utc = new DateTimeZone('UTC');
		$dtStr = gmdate("c", (int) $timestamp); //Have to do it this way because of PHP 5.2
		$dt = new DateTime($dtStr, $utc);
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$dt->setTimezone(new DateTimeZone($tz));
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				if (PHP_VERSION_ID < 50510) {
					$dtStr = gmdate("c", (int) ($timestamp + $gmt * 3600)); //Have to do it this way because of < PHP 5.5.10
					$dt = new DateTime($dtStr, $utc);
				}
				else {
					$direction = ($gmt > 0 ? '+' : '-');
					$gmt = abs($gmt);
					$h = (int) $gmt;
					$m = ($gmt - $h) * 60;
					$dt->setTimezone(new DateTimeZone($direction . str_pad($h, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT)));
				}
			}
		}
		return $dt->format($format);
	}
	
	/**
	 * Parses the given time string and returns its DateTime with the server's configured time zone.
	 * 
	 * @param string $timestring
	 * @return DateTime
	 */
	public static function parseLocalTime($timestring) {
		$utc = new DateTimeZone('UTC');
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$tz = new DateTimeZone($tz);
			return new DateTime($timestring, $tz);
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				if (PHP_VERSION_ID < 50510) {
					$timestamp = strtotime($timestring);
					$dtStr = gmdate("c", (int) ($timestamp + $gmt * 3600)); //Have to do it this way because of < PHP 5.5.10
					return new DateTime($dtStr, $utc);
				}
				else {
					$direction = ($gmt > 0 ? '+' : '-');
					$gmt = abs($gmt);
					$h = (int) $gmt;
					$m = ($gmt - $h) * 60;
					$tz = new DateTimeZone($direction . str_pad($h, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT));
					return new DateTime($timestring, $tz);
				}
			}
		}
		return new DateTime($timestring);
	}
	
	/**
	 * Base64URL-encodes the given payload. This is identical to base64_encode except it substitutes characters
	 * not safe for use in URLs.
	 * 
	 * @param string $payload
	 * @return string
	 */
	public static function base64url_encode($payload) {
		$intermediate = base64_encode($payload);
		$intermediate = rtrim($intermediate, '=');
		$intermediate = str_replace('+', '-', $intermediate);
		$intermediate = str_replace('/', '_', $intermediate);
		return $intermediate;
	}
	
	/**
	 * Base64URL-decodes the given payload. This is identical to base64_encode except it allows for the characters
	 * substituted by base64url_encode.
	 * 
	 * @param string $payload
	 * @return string
	 */
	public static function base64url_decode($payload) {
		$intermediate = str_replace('_', '/', $payload);
		$intermediate = str_replace('-', '+', $intermediate);
		$intermediate = base64_decode($intermediate);
		return $intermediate;
	}
	
	/**
	 * Returns a signed JWT for the given payload. Payload is expected to be an array suitable for JSON-encoding.
	 * 
	 * @param array $payload
	 * @param int $maxAge How long the JWT will be considered valid.
	 * @return string
	 */
	public static function generateJWT($payload, $maxAge = 604800 /* 7 days */) {
		$payload['_exp'] = time() + $maxAge;
		$key = wfConfig::get('longEncKey');
		$header = '{"alg":"HS256","typ":"JWT"}';
		$body = self::base64url_encode($header) . '.' . self::base64url_encode(json_encode($payload));
		$signature = hash_hmac('sha256', $body, $key, true);
		return $body . '.' . self::base64url_encode($signature);
	}
	
	/**
	 * Decodes and returns the payload of a JWT. This also validates the signature.
	 * 
	 * @param string $token
	 * @return array|bool The decoded payload or false if the token is invalid or fails validation.
	 */
	public static function decodeJWT($token) {
		$components = explode('.', $token);
		if (count($components) != 3) {
			return false;
		}
		
		$key = wfConfig::get('longEncKey');
		$body = $components[0] . '.' . $components[1];
		$signature = hash_hmac('sha256', $body, $key, true);
		$testSignature = self::base64url_decode($components[2]);
		if (!hash_equals($signature, $testSignature)) {
			return false;
		}
		
		$json = self::base64url_decode($components[1]);
		$payload = @json_decode($json, true);
		if (!is_array($payload) || (isset($payload['_exp']) && $payload['_exp'] < time())) {
			return false;
		}
		return $payload;
	}

	/**
	 * Split a path into its components
	 * @param string $path
	 */
	public static function splitPath($path) {
		return preg_split('/[\\/\\\\]/', $path, -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Convert an absolute path to a path relative to $to
	 * @param string $absolute the absolute path to convert
	 * @param string $to the absolute path from which to derive the relative path
	 * @param bool $leadingSlash if true, prepend the resultant URL with a slash
	 */
	public static function relativePath($absolute, $to, $leadingSlash = false) {
		$trailingSlash = in_array(substr($absolute, -1), array('/', '\\'));
		$absoluteComponents = self::splitPath($absolute);
		$toComponents = self::splitPath($to);
		$relativeComponents = array();
		do {
			$currentAbsolute = array_shift($absoluteComponents);
			$currentTo = array_shift($toComponents);
		} while($currentAbsolute === $currentTo && $currentAbsolute !== null);
		while ($currentTo !== null) {
			array_push($relativeComponents, '..');
			$currentTo = array_shift($toComponents);
		}
		while ($currentAbsolute !== null) {
			array_push($relativeComponents, $currentAbsolute);
			$currentAbsolute = array_shift($absoluteComponents);
		}
		return implode(array(
			$leadingSlash ? '/' : '',
			implode('/', $relativeComponents),
			($trailingSlash && (count($relativeComponents) > 0 || !$leadingSlash)) ? '/' : ''
		));
	}

	/**
	 * Determine the effective port given the output of parse_url
	 * @param array $urlComponents
	 * @return int the resolved port number
	 */
	private static function resolvePort($urlComponents) {
		if (array_key_exists('port', $urlComponents) && !empty($urlComponents['port'])) {
			return $urlComponents['port'];
		}
		if (array_key_exists('scheme', $urlComponents) && $urlComponents['scheme'] === 'https') {
			return 443;
		}
		return 80;
	}

	/**
	 * Check if two site URLs identify the same site
	 * @param string $a first url
	 * @param string $b second url
	 * @param array $ignoredSubdomains An array of subdomains to ignore when matching (e.g., www)
	 * @return bool true if the URLs match, false otherwise
	 */
	public static function compareSiteUrls($a, $b, $ignoredSubdomains = array()) {
		$patterns = array_map(function($p) { return '/^' . preg_quote($p, '/') . '\\./i'; }, $ignoredSubdomains);
		
		$componentsA = parse_url($a);
		if (isset($componentsA['host'])) { $componentsA['host'] = preg_replace($patterns, '', $componentsA['host']); }
		$componentsB = parse_url($b);
		if (isset($componentsB['host'])) { $componentsB['host'] = preg_replace($patterns, '', $componentsB['host']); }
		foreach (array('host', 'port', 'path') as $component) {
			$valueA = array_key_exists($component, $componentsA) ? $componentsA[$component] : null;
			$valueB = array_key_exists($component, $componentsB) ? $componentsB[$component] : null;
			if ($valueA !== $valueB) {
				if ($component === 'port') {
					$portA = self::resolvePort($componentsA);
					$portB = self::resolvePort($componentsB);
					if ($portA !== $portB)
						return false;
				}
				else {
					return false;
				}
			}
		}
		return true;
	}

	public static function getHomePath() {
		if (!function_exists('get_home_path')) {
			include_once(ABSPATH . 'wp-admin/includes/file.php');
		}
		if (WF_IS_FLYWHEEL)
			return trailingslashit($_SERVER['DOCUMENT_ROOT']);
		return get_home_path();
	}

	public static function includeOnceIfPresent($path) {
		if (file_exists($path) && is_readable($path)) {
			@include_once($path);
			return @include_once($path); //Calling `include_once` for an already included file will return true
		}
		return false;
	}

	public static function isCurlSupported() {
		if (!function_exists('curl_init') || !function_exists('curl_exec')) {
			return false;
		}
		
		$is_ssl = isset($args['ssl']) && $args['ssl'];
		
		if ($is_ssl) {
			$curl_version = curl_version();
			// Check whether this cURL version support SSL requests.
			if (!(CURL_VERSION_SSL & $curl_version['features'])) {
				return false;
			}
		}
		
		return true;
	}

	private static function isValidJsonValue($value) {
		return json_encode($value) !== false;
	}

	private static function filterInvalidJsonValues($data, &$modified, &$valid = null) {
		if (is_array($data)) {
			$modified = array();
			$filtered = array();
			$valid = true;
			foreach ($data as $key => $value) {
				$value = self::filterInvalidJsonValues($value, $itemModified, $itemValid);
				if (($itemValid || $itemModified) && self::isValidJsonValue(array($key => $value))) {
					$filtered[$key] = $value;
					if ($itemModified)
						$modified[$key] = $itemModified;
				}
				else {
					$valid = false;
				}
			}
			return $filtered;
		}
		else {
			$modified = false;
			$valid = self::isValidJsonValue($data);
			if ($valid) {
				return $data;
			}
			else if (is_string($data)) {
				$modified = true;
				return base64_encode($data);
			}
			else {
				return null;
			}
		}
	}

	public static function jsonEncodeSafely($data) {
		$encoded = json_encode($data);
		if ($encoded === false) {
			$data = self::filterInvalidJsonValues($data, $modified);
			if ($modified)
				$data['__modified__'] = $modified;
			$encoded = json_encode($data);
		}
		return $encoded;
	}
	
	/**
	 * Convenience function to extract a matched pattern from a string. If $pattern has no matching groups, the entire
	 * matched portion is returned. If it has at least one matching group, the first one is returned (others are 
	 * ignored). If there is no match, false is returned.
	 * 
	 * @param string $pattern
	 * @param string $subject
	 * @param bool $expandToLine Whether or not to expand the captured value to include the entire line's contents
	 * @return false|string
	 */
	public static function pregExtract($pattern, $subject, $expandToLine = false) {
		if (preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE)) {
			if (count($matches) > 1) {
				$start = $matches[1][1];
				$text = $matches[1][0];
				$end = $start + strlen($text);
			}
			else {
				$start = $matches[0][1];
				$text = $matches[0][0];
				$end = $start + strlen($text);
			}
			
			if ($expandToLine) {
				if (preg_match_all('/[\r\n]/', substr($subject, 0, $start), $matches, PREG_OFFSET_CAPTURE)) {
					$start = $matches[0][count($matches[0]) - 1][1] + 1;
				}
				else {
					$start = 0;
				}
				
				if (preg_match('/[\r\n]/', $subject, $matches, PREG_OFFSET_CAPTURE, $end)) {
					$end = $matches[0][1];
				}
				else {
					$end = strlen($subject) - 0;
				}
				
				$text = substr($subject, $start, $end - $start);
			}
			
			return $text;
		}
		return false;
	}
	
	/**
	 * Returns whether or not MySQLi should be used directly when needed. Returns true if there's a valid DB handle,
	 * our database test succeeded, our constant is not set to prevent it, and then either $wpdb indicates it's using
	 * mysqli (older WordPress versions) or we're on PHP 7+ (only mysqli is ever used).
	 * 
	 * @return bool
	 */
	public static function useMySQLi() {
		global $wpdb;
		$dbh = $wpdb->dbh;
		$useMySQLi = (is_object($dbh) && (PHP_MAJOR_VERSION >= 7 || $wpdb->use_mysqli) && wfConfig::get('allowMySQLi', true) && WORDFENCE_ALLOW_DIRECT_MYSQLI);
		return $useMySQLi;
	}
}

// GeoIP lib uses these as well
if (!function_exists('inet_ntop')) {
	function inet_ntop($ip) {
		return wfUtils::_inet_ntop($ip);
	}
}
if (!function_exists('inet_pton')) {
	function inet_pton($ip) {
		return wfUtils::_inet_pton($ip);
	}
}


class wfWebServerInfo {

	const APACHE = 1;
	const NGINX = 2;
	const LITESPEED = 4;
	const IIS = 8;

	private $handler;
	private $software;
	private $softwareName;

	/**
	 *
	 */
	public static function createFromEnvironment() {
		$serverInfo = new self;
		$sapi = php_sapi_name();
		if (WF_IS_FLYWHEEL) {
			$serverInfo->setSoftware(self::NGINX);
			$serverInfo->setSoftwareName('Flywheel');
		}
		else if (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false) {
			$serverInfo->setSoftware(self::IIS);
			$serverInfo->setSoftwareName('iis');
		}
		else if (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
			$serverInfo->setSoftware(self::NGINX);
			$serverInfo->setSoftwareName('nginx');
		}
		else if (stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false || $sapi == 'litespeed') {
			$serverInfo->setSoftware(self::LITESPEED);
			$serverInfo->setSoftwareName('litespeed');
		}
		else if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) {
			$serverInfo->setSoftware(self::APACHE);
			$serverInfo->setSoftwareName('apache');
		}
		else if (stripos($_SERVER['SERVER_SOFTWARE'], 'unit') !== false) {
			$serverInfo->setSoftware(self::NGINX);
			$serverInfo->setSoftwareName('unit');
		}

		$serverInfo->setHandler($sapi);

		return $serverInfo;
	}

	/**
	 * @return bool
	 */
	public function isApache() {
		return $this->getSoftware() === self::APACHE;
	}

	/**
	 * @return bool
	 */
	public function isNginx() {
		return $this->getSoftware() === self::NGINX;
	}

	/**
	 * @return bool
	 */
	public function isLiteSpeed() {
		return $this->getSoftware() === self::LITESPEED;
	}

	/**
	 * @return bool
	 */
	public function isIIS() {
		return $this->getSoftware() === self::IIS;
	}

	/**
	 * @return bool
	 */
	public function isApacheModPHP() {
		return $this->isApache() && function_exists('apache_get_modules');
	}

	/**
	 * Not sure if this can be implemented at the PHP level.
	 * @return bool
	 */
	public function isApacheSuPHP() {
		return $this->isApache() && $this->isCGI() &&
			function_exists('posix_getuid') &&
			function_exists('getmyuid') &&
			getmyuid() === posix_getuid();
	}

	private function isUnit() {
		return $this->softwareName == "unit";
	}

	public function isNginxStandard() {
		return $this->isNginx() && !$this->isUnit();
	}

	public function isNginxUnit() {
		return $this->isNginx() && $this->isUnit();
	}

	/**
	 * @return bool
	 */
	public function isCGI() {
		return !$this->isFastCGI() && stripos($this->getHandler(), 'cgi') !== false;
	}

	/**
	 * @return bool
	 */
	public function isFastCGI() {
		return stripos($this->getHandler(), 'fastcgi') !== false || stripos($this->getHandler(), 'fpm-fcgi') !== false;
	}

	/**
	 * @return mixed
	 */
	public function getHandler() {
		return $this->handler;
	}

	/**
	 * @param mixed $handler
	 */
	public function setHandler($handler) {
		$this->handler = $handler;
	}

	/**
	 * @return mixed
	 */
	public function getSoftware() {
		return $this->software;
	}

	/**
	 * @param mixed $software
	 */
	public function setSoftware($software) {
		$this->software = $software;
	}

	/**
	 * @return mixed
	 */
	public function getSoftwareName() {
		return $this->softwareName;
	}

	/**
	 * @param mixed $softwareName
	 */
	public function setSoftwareName($softwareName) {
		$this->softwareName = $softwareName;
	}
}