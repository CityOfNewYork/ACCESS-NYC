<?php

namespace WordfenceLS;

class Model_Request {
	const IP_SOURCE_AUTOMATIC = '';
	const IP_SOURCE_REMOTE_ADDR = 'REMOTE_ADDR';
	const IP_SOURCE_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';
	const IP_SOURCE_X_REAL_IP = 'HTTP_X_REAL_IP';
	
	private $_cachedIP;
	
	public static function current() {
		return new Model_Request();
	}
	
	public function detected_ip_preview($source = null, $trusted_proxies = null) {
		if ($source === null) {
			$source = Controller_Settings::shared()->get(Controller_Settings::OPTION_IP_SOURCE);
		}
		
		$record = $this->_ip($source);
		if (is_array($record)) {
			list($ip, $variable) = $record;
			if (isset($_SERVER[$variable]) && strpos($_SERVER[$variable], ',') !== false) {
				$items = preg_replace('/[\s,]/', '', explode(',', $_SERVER[$variable]));
				$output = '';
				foreach ($items as $i) {
					if ($ip == $i) {
						$output .= ', <strong>' . esc_html($i) . '</strong>';
					}
					else {
						$output .= ', ' . esc_html($i);
					}
				}
				
				return substr($output, 2);
			}
			return '<strong>' . esc_html($ip) . '</strong>';
		}
		return false;
	}
	
	public function ip($refreshCache = false) {
		if (WORDFENCE_LS_FROM_CORE) {
			return \wfUtils::getIP($refreshCache);
		}
		
		if (!isset($this->_cachedIP) || $refreshCache) {
			$this->_cachedIP = $this->_ip(Controller_Settings::shared()->get(Controller_Settings::OPTION_IP_SOURCE), Controller_Settings::shared()->trusted_proxies());
		}
		
		return $this->_cachedIP[0]; //Format is array(<text IP>, <field found in>)
	}
	
	public function ip_for_field($source, $trusted_proxies) {
		return $this->_ip($source, $trusted_proxies);
	}
	
	protected function _ip($source = null, $trusted_proxies = null) {
		if ($source === null) {
			$source = Controller_Settings::shared()->get(Controller_Settings::OPTION_IP_SOURCE);
		}
		
		$possible_ips = $this->_possible_ips($source);
		if ($trusted_proxies === null) { $trusted_proxies = array(); }
		return $this->_find_preferred_ip($possible_ips, $trusted_proxies);
	}
	
	protected function _possible_ips($source = null) {
		$defaultIP = (is_array($_SERVER) && isset($_SERVER[self::IP_SOURCE_REMOTE_ADDR])) ? array($_SERVER[self::IP_SOURCE_REMOTE_ADDR], self::IP_SOURCE_REMOTE_ADDR) : array('127.0.0.1', self::IP_SOURCE_REMOTE_ADDR);
		
		if ($source) {
			if ($source == self::IP_SOURCE_REMOTE_ADDR) {
				return array($defaultIP);
			}
			
			$check = array(
				array((isset($_SERVER[$source]) ? $_SERVER[$source] : ''), $source),
				$defaultIP,
			);
			return $check;
		}
		
		$check = array($defaultIP);
		if (isset($_SERVER[self::IP_SOURCE_X_FORWARDED_FOR])) {
			$check[] = array($_SERVER[self::IP_SOURCE_X_FORWARDED_FOR], self::IP_SOURCE_X_FORWARDED_FOR);
		}
		if (isset($_SERVER[self::IP_SOURCE_X_REAL_IP])) {
			$check[] = array($_SERVER[self::IP_SOURCE_X_REAL_IP], self::IP_SOURCE_X_REAL_IP);
		}
		return $check;
	}
	
	protected function _find_preferred_ip($possible_ips, $trusted_proxies) {
		$privates = array();
		foreach ($possible_ips as $entry) {
			list($value, $var) = $entry;
			if (is_array($value)) { // An array of IPs
				foreach ($value as $index => $j) {
					if (!Model_IP::is_valid_ip($j)) {
						$j = preg_replace('/:\d+$/', '', $j); //Strip off port if present
					}
					
					if (Model_IP::is_valid_ip($j)) {
						if (Model_IP::is_ipv6_mapped_ipv4($j)) {
							$j = Model_IP::inet_ntop(Model_IP::inet_pton($j));
						}
						
						foreach ($trusted_proxies as $proxy) {
							if (!empty($proxy)) {
								if (Controller_Whitelist::shared()->ip_in_range($j, $proxy) && $index < count($value) - 1) {
									continue 2;
								}
							}
						}
						
						if (filter_var($j, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
							$privates[] = array($j, $var);
						}
						else {
							return array($j, $var);
						}
					}
				}
				continue;
			}
			
			$skipToNext = false;
			$separators = array(',', ' ', "\t");
			foreach ($separators as $char) { // A list of IPs separated by <separator>: 192.0.2.15,192.0.2.35,192.0.2.254
				if (strpos($value, $char) !== false) {
					$sp = explode($char, $value);
					$sp = array_reverse($sp);
					foreach ($sp as $index => $j) {
						$j = trim($j);
						if (!Model_IP::is_valid_ip($j)) {
							$j = preg_replace('/:\d+$/', '', $j); //Strip off port
						}
						
						if (Model_IP::is_valid_ip($j)) {
							if (Model_IP::is_ipv6_mapped_ipv4($j)) {
								$j = Model_IP::inet_ntop(Model_IP::inet_pton($j));
							}
							
							foreach ($trusted_proxies as $proxy) {
								if (!empty($proxy)) {
									if (Controller_Whitelist::shared()->ip_in_range($j, $proxy) && $index < count($sp) - 1) {
										continue 2;
									}
								}
							}
							
							if (filter_var($j, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
								$privates[] = array($j, $var);
							}
							else {
								return array($j, $var);
							}
						}
					}
					$skipToNext = true;
					break;
				}
			}
			if ($skipToNext) { continue; } //Skip to next item because this one had a comma/space/tab, but we didn't find a valid, non-private address
			
			// A literal IP
			if (!Model_IP::is_valid_ip($value)) {
				$value = preg_replace('/:\d+$/', '', $value); //Strip off port
			}
			
			if (Model_IP::is_valid_ip($value)) {
				if (Model_IP::is_ipv6_mapped_ipv4($value)) {
					$value = Model_IP::inet_ntop(Model_IP::inet_pton($value));
				}
				
				if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
					$privates[] = array($value, $var);
				}
				else {
					return array($value, $var);
				}
			}
		}
		
		if (count($privates) > 0) {
			return $privates[0];
		}
		
		return false;
	}
}