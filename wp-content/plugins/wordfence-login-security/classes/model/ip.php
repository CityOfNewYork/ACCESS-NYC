<?php

namespace WordfenceLS;

class Model_IP {
	/**
	 * Returns the human-readable representation of a packed binary IP address.
	 *
	 * @param string $ip
	 * @return bool|string
	 */
	public static function inet_ntop($ip) {
		if (Model_Crypto::strlen($ip) == 16 && Model_Crypto::substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
			$ip = Model_Crypto::substr($ip, 12, 4);
		}
		
		if (self::has_ipv6()) {
			return @inet_ntop($ip);
		}
		
		// IPv4
		if (Model_Crypto::strlen($ip) === 4) {
			return ord($ip[0]) . '.' . ord($ip[1]) . '.' . ord($ip[2]) . '.' . ord($ip[3]);
		}
		
		// IPv6
		if (Model_Crypto::strlen($ip) === 16) {
			// IPv4 mapped IPv6
			if (Model_Crypto::substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
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
	 * Returns the packed binary representation of an IP address from the human readable version.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function inet_pton($ip) {
		if (self::has_ipv6()) {
			$pton = @inet_pton($ip);
			if ($pton === false) {
				return false;
			}
		}
		else {
			if (preg_match('/^(?:\d{1,3}(?:\.|$)){4}/', $ip)) { // IPv4
				$octets = explode('.', $ip);
				$pton = chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
			}
			else if (preg_match('/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $ip)) { // IPv6
				if ($ip === '::') {
					$pton = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
				}
				else {
					$colon_count = substr_count($ip, ':');
					$dbl_colon_pos = strpos($ip, '::');
					if ($dbl_colon_pos !== false) {
						$ip = str_replace('::', str_repeat(':0000', (($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
						$ip = trim($ip, ':');
					}
					
					$ip_groups = explode(':', $ip);
					$ipv6_bin = '';
					foreach ($ip_groups as $ip_group) {
						$ipv6_bin .= pack('H*', str_pad($ip_group, 4, '0', STR_PAD_LEFT));
					}
					
					if (Model_Crypto::strlen($ipv6_bin) == 16) {
						$pton = $ipv6_bin;
					}
					else {
						return false;
					}
				}
			}
			else if (preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $matches)) { // IPv4 mapped IPv6
				$octets = explode('.', $matches[1]);
				$pton = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
			}
			else {
				return false;
			}
		}
		
		$pton = str_pad($pton, 16, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\x00\x00", STR_PAD_LEFT);
		return $pton;
	}
	
	/**
	 * Verify PHP was compiled with IPv6 support.
	 *
	 * Some hosts appear to not have inet_ntop, and others appear to have inet_ntop but are unable to process IPv6 addresses.
	 *
	 * @return bool
	 */
	public static function has_ipv6() {
		return defined('AF_INET6');
	}
	
	/**
	 * Expands a compressed printable representation of an IPv6 address.
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function expand_ipv6_address($ip) {
		$hex = bin2hex(self::inet_pton($ip));
		$ip = substr(preg_replace("/([a-f0-9]{4})/i", "$1:", $hex), 0, -1);
		return $ip;
	}
	
	/**
	 * Returns whether or not the IP is a valid format.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public static function is_valid_ip($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}
	
	/**
	 * Returns whether or not the range is a valid CIDR range.
	 * 
	 * @param string $range
	 * @return bool
	 */
	public static function is_valid_cidr_range($range) {
		$components = explode('/', $range);
		if (count($components) != 2) { return false; }
		
		list($ip, $prefix) = $components;
		if (!self::is_valid_ip($ip)) { return false; }
		
		if (!preg_match('/^\d+$/', $prefix)) { return false; }
		
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if ($prefix < 0 || $prefix > 32) { return false; }
		}
		else {
			if ($prefix < 1 || $prefix > 128) { return false; }
		}
		
		return true;
	}
	
	/**
	 * Returns whether or not the IP is in the IPv6-mapped-IPv4 format.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public static function is_ipv6_mapped_ipv4($ip) {
		return preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $ip) > 0;
	}
}