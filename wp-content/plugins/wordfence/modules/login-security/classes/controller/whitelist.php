<?php

namespace WordfenceLS;

class Controller_Whitelist {
	private $_cachedStatus = array();
	
	/**
	 * Returns the singleton Controller_Whitelist.
	 *
	 * @return Controller_Whitelist
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Whitelist();
		}
		return $_shared;
	}
	
	public function is_whitelisted($ip) {
		$ipHash = hash('sha256', Model_IP::inet_pton($ip));
		if (isset($this->_cachedStatus[$ipHash])) {
			return $this->_cachedStatus[$ipHash];
		}
		
		$whitelist = Controller_Settings::shared()->whitelisted_ips();
		foreach ($whitelist as $entry) {
			if ($this->ip_in_range($ip, $entry)) {
				$this->_cachedStatus[$ipHash] = true;
				return true;
			}
		}
		$this->_cachedStatus[$ipHash] = false;
		return false;
	}
	
	/**
	 * Check if the supplied IP address is within the user supplied range.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function ip_in_range($ip, $range) {
		if (strpos($range, '/') !== false) { //CIDR range -- 127.0.0.1/24
			return $this->_cidr_contains_ip($range, $ip);
		}
		else if (strpos($range, '[') !== false) { //Bracketed range -- 127.0.0.[1-100]
			// IPv4 range
			if (strpos($range, '.') !== false && strpos($ip, '.') !== false) {
				// IPv4-mapped-IPv6
				if (preg_match('/:ffff:([^:]+)$/i', $range, $matches)) {
					$range = $matches[1];
				}
				if (preg_match('/:ffff:([^:]+)$/i', $ip, $matches)) {
					$ip = $matches[1];
				}
				
				// Range check
				if (preg_match('/\[\d+\-\d+\]/', $range)) {
					$ipParts = explode('.', $ip);
					$whiteParts = explode('.', $range);
					$mismatch = false;
					if (count($whiteParts) != 4 || count($ipParts) != 4) {
						return false;
					}
					
					for ($i = 0; $i <= 3; $i++) {
						if (preg_match('/^\[(\d+)\-(\d+)\]$/', $whiteParts[$i], $m)) {
							if ($ipParts[$i] < $m[1] || $ipParts[$i] > $m[2]) {
								$mismatch = true;
							}
						}
						else if ($whiteParts[$i] != $ipParts[$i]) {
							$mismatch = true;
						}
					}
					if ($mismatch === false) {
						return true; // Is whitelisted because we did not get a mismatch
					}
				}
				else if ($range == $ip) {
					return true;
				}
				
				// IPv6 range
			}
			else if (strpos($range, ':') !== false && strpos($ip, ':') !== false) {
				$ip = strtolower(Model_IP::expand_ipv6_address($ip));
				$range = strtolower($this->_expand_ipv6_range($range));
				if (preg_match('/\[[a-f0-9]+\-[a-f0-9]+\]/i', $range)) {
					$IPparts = explode(':', $ip);
					$whiteParts = explode(':', $range);
					$mismatch = false;
					if (count($whiteParts) != 8 || count($IPparts) != 8) {
						return false;
					}
					
					for ($i = 0; $i <= 7; $i++) {
						if (preg_match('/^\[([a-f0-9]+)\-([a-f0-9]+)\]$/i', $whiteParts[$i], $m)) {
							$ip_group = hexdec($IPparts[$i]);
							$range_group_from = hexdec($m[1]);
							$range_group_to = hexdec($m[2]);
							if ($ip_group < $range_group_from || $ip_group > $range_group_to) {
								$mismatch = true;
								break;
							}
						}
						else if ($whiteParts[$i] != $IPparts[$i]) {
							$mismatch = true;
							break;
						}
					}
					if ($mismatch === false) {
						return true; // Is whitelisted because we did not get a mismatch
					}
				}
				else if ($range == $ip) {
					return true;
				}
			}
		}
		else if (strpos($range, '-') !== false) { //Linear range -- 127.0.0.1 - 127.0.1.100
			list($ip1, $ip2) = explode('-', $range);
			$ip1N = Model_IP::inet_pton($ip1);
			$ip2N = Model_IP::inet_pton($ip2);
			$ipN = Model_IP::inet_pton($ip);
			return (strcmp($ip1N, $ipN) <= 0 && strcmp($ip2N, $ipN) >= 0);
		}
		else { //Treat as a literal IP
			$ip1 = Model_IP::inet_pton($range);
			$ip2 = Model_IP::inet_pton($ip);
			if ($ip1 !== false && $ip1 === $ip2) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Utility
	 */
	
	/**
	 * Returns whether or not the CIDR-formatted subnet contains $ip.
	 * 
	 * @param string $subnet
	 * @param string $ip A human-readable IP.
	 * @return bool
	 */
	protected function _cidr_contains_ip($subnet, $ip) {
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
		
		$bin_network = Model_Crypto::substr(Model_IP::inet_pton($network), 0, ceil($prefix / 8));
		$bin_ip = Model_Crypto::substr(Model_IP::inet_pton($ip), 0, ceil($prefix / 8));
		if ($prefix % 8 != 0) { //Adjust the last relevant character to fit the mask length since the character's bits are split over it
			$pos = intval($prefix / 8);
			$adjustment = chr(((0xff << (8 - ($prefix % 8))) & 0xff));
			$bin_network[$pos] = ($bin_network[$pos] & $adjustment);
			$bin_ip[$pos] = ($bin_ip[$pos] & $adjustment);
		}
		
		return ($bin_network === $bin_ip);
	}
	
	/**
	 * Expands a compressed printable range representation of an IPv6 address.
	 *
	 * @param string $range
	 * @return string
	 */
	protected function _expand_ipv6_range($range) {
		$colon_count = substr_count($range, ':');
		$dbl_colon_count = substr_count($range, '::');
		if ($dbl_colon_count > 1) {
			return false;
		}
		$dbl_colon_pos = strpos($range, '::');
		if ($dbl_colon_pos !== false) {
			$range = str_replace('::', str_repeat(':0000', (($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($range) - 2) ? 9 : 8) - $colon_count) . ':', $range);
			$range = trim($range, ':');
		}
		$colon_count = substr_count($range, ':');
		if ($colon_count != 7) {
			return false;
		}
		
		$groups = explode(':', $range);
		$expanded = '';
		foreach ($groups as $group) {
			if (preg_match('/\[([a-f0-9]{1,4})\-([a-f0-9]{1,4})\]/i', $group, $matches)) {
				$expanded .= sprintf('[%s-%s]', str_pad(strtolower($matches[1]), 4, '0', STR_PAD_LEFT), str_pad(strtolower($matches[2]), 4, '0', STR_PAD_LEFT)) . ':';
			}
			else if (preg_match('/[a-f0-9]{1,4}/i', $group)) {
				$expanded .= str_pad(strtolower($group), 4, '0', STR_PAD_LEFT) . ':';
			}
			else {
				return false;
			}
		}
		return trim($expanded, ':');
	}
	
	/**
	 * @return bool
	 */
	public function is_valid_range($range) {
		return $this->_is_valid_cidr_range($range) || $this->_is_valid_bracketed_range($range) || $this->_is_valid_linear_range($range) || Model_IP::is_valid_ip($range);
	}
	
	protected function _is_valid_cidr_range($range) { //e.g., 192.0.2.1/24
		if (preg_match('/[^0-9a-f:\/\.]/i', $range)) { return false; }
		$components = explode('/', $range);
		if (count($components) != 2) { return false; }
		
		list($ip, $prefix) = $components;
		if (!Model_IP::is_valid_ip($ip)) { return false; }
		
		if (!preg_match('/^\d+$/', $prefix)) { return false; }
		
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if ($prefix < 0 || $prefix > 32) { return false; }
		}
		else {
			if ($prefix < 1 || $prefix > 128) { return false; }
		}
		
		return true;
	}
	
	protected function _is_valid_bracketed_range($range) { //e.g., 192.0.2.[1-10]
		if (preg_match('/[^0-9a-f:\.\[\]\-]/i', $range)) { return false; }
		if (strpos($range, '.') !== false) { //IPv4
			if (preg_match_all('/(\d+)/', $range, $matches) > 0) {
				foreach ($matches[1] as $match) {
					$group = (int) $match;
					if ($group > 255 || $group < 0) {
						return false;
					}
				}
			}
			
			$group_regex = '([0-9]{1,3}|\[[0-9]{1,3}\-[0-9]{1,3}\])';
			return preg_match('/^' . str_repeat("{$group_regex}\\.", 3) . $group_regex . '$/i', $range) > 0;
		}
		
		//IPv6
		if (strpos($range, '::') !== false) {
			$range = $this->_expand_ipv6_range($range);
		}
		
		if (!$range) {
			return false;
		}
		$group_regex = '([a-f0-9]{1,4}|\[[a-f0-9]{1,4}\-[a-f0-9]{1,4}\])';
		return preg_match('/^' . str_repeat($group_regex . ':', 7) . $group_regex . '$/i', $range) > 0;
	}
	
	protected function _is_valid_linear_range($range) { //e.g., 192.0.2.1-192.0.2.100
		if (preg_match('/[^0-9a-f:\.\-]/i', $range)) { return false; }
		list($ip1, $ip2) = explode("-", $range);
		$ip1N = Model_IP::inet_pton($ip1);
		$ip2N = Model_IP::inet_pton($ip2);
		
		if ($ip1N === false || !Model_IP::is_valid_ip($ip1) || $ip2N === false || !Model_IP::is_valid_ip($ip2)) {
			return false;
		}
		
		return strcmp($ip1N, $ip2N) <= 0;
	}
	
	protected function _is_mixed_range($range) { //e.g., 192.0.2.1-2001:db8::ffff
		if (preg_match('/[^0-9a-f:\.\-]/i', $range)) { return false; }
		list($ip1, $ip2) = explode("-", $range);
		
		$ipv4Count = 0;
		$ipv4Count += filter_var($ip1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? 1 : 0;
		$ipv4Count += filter_var($ip2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? 1 : 0;
		
		$ipv6Count = 0;
		$ipv6Count += filter_var($ip1, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 1 : 0;
		$ipv6Count += filter_var($ip2, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 1 : 0;
		
		if ($ipv4Count != 2 && $ipv6Count != 2) {
			return true;
		}
		
		return false;
	}
}