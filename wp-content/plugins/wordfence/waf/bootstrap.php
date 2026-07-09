<?php

/*
	php_value auto_prepend_file ~/wp-content/plugins/wordfence/waf/bootstrap.php
*/

if (!defined('WFWAF_RUN_COMPLETE')) {

if (!defined('WFWAF_AUTO_PREPEND')) {
	define('WFWAF_AUTO_PREPEND', true);
}
if (!defined('WF_IS_WP_ENGINE')) {
	define('WF_IS_WP_ENGINE', isset($_SERVER['IS_WPE']));
}
if (!defined('WF_IS_FLYWHEEL')) {
	define('WF_IS_FLYWHEEL', isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Flywheel/') === 0);
}
if (!defined('WF_IS_PRESSABLE')) {
	define('WF_IS_PRESSABLE', (defined('IS_ATOMIC') && IS_ATOMIC) || (defined('IS_PRESSABLE') && IS_PRESSABLE));
}

require(dirname(__FILE__) . '/../lib/wfVersionSupport.php');
/**
 * @var string $wfPHPDeprecatingVersion
 * @var string $wfPHPMinimumVersion
 */

if (!defined('WF_PHP_UNSUPPORTED')) {
	define('WF_PHP_UNSUPPORTED', version_compare(PHP_VERSION, $wfPHPMinimumVersion, '<'));
}

if (WF_PHP_UNSUPPORTED) {
	return;
}



require_once(dirname(__FILE__) . '/wfWAFUserIPRange.php');
require_once(dirname(__FILE__) . '/wfWAFIPBlocksController.php');
require_once(dirname(__FILE__) . '/../vendor/wordfence/wf-waf/src/init.php');

class wfWAFWordPressRequest extends wfWAFRequest {
	
	/**
	 * @param wfWAFRequest|null $request
	 * @return wfWAFRequest
	 */
	public static function createFromGlobals($request = null) {
		if (version_compare(phpversion(), '5.3.0') >= 0) {
			$class = get_called_class();
			$request = new $class();
		} else {
			$request = new self();
		}
		return parent::createFromGlobals($request);
	}

	public function getIP() {
		static $theIP = null;
		if (isset($theIP)) {
			return $theIP;
		}
		$ips = array();
		$howGet = wfWAF::getInstance()->getStorageEngine()->getConfig('howGetIPs', null, 'synced');
		if ($howGet) {
			if (is_string($howGet) && is_array($_SERVER) && array_key_exists($howGet, $_SERVER)) {
				$ips[] = array($_SERVER[$howGet], $howGet);
			}
			
			if ($howGet != 'REMOTE_ADDR') {
				$ips[] = array((is_array($_SERVER) && array_key_exists('REMOTE_ADDR', $_SERVER)) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1', 'REMOTE_ADDR');
			}
		}
		else {
			$recommendedField = wfWAF::getInstance()->getStorageEngine()->getConfig('detectProxyRecommendation', null, 'synced');
			if (!empty($recommendedField) && $recommendedField != 'UNKNOWN' && $recommendedField != 'DEFERRED') {
				if (isset($_SERVER[$recommendedField])) {
					$ips[] = array($_SERVER[$recommendedField], $recommendedField);
				}
			}
			
			$ips[] = array((is_array($_SERVER) && array_key_exists('REMOTE_ADDR', $_SERVER)) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1', 'REMOTE_ADDR');
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ips[] = array($_SERVER['HTTP_X_FORWARDED_FOR'], 'HTTP_X_FORWARDED_FOR');
			}
			if (isset($_SERVER['HTTP_X_REAL_IP'])) {
				$ips[] = array($_SERVER['HTTP_X_REAL_IP'], 'HTTP_X_REAL_IP');
			}
		}
		
		$cleanedIP = $this->_getCleanIPAndServerVar($ips);
		if (is_array($cleanedIP)) {
			list($ip, $variable) = $cleanedIP;
			$theIP = $ip;
			return $ip;
		}
		$theIP = $cleanedIP;
		return $cleanedIP;
	}
	
	/**
	 * Expects an array of items. The items are either IPs or IPs separated by comma, space or tab. Or an array of IP's.
	 * We then examine all IP's looking for a public IP and storing private IP's in an array. If we find no public IPs we return the first private addr we found.
	 *
	 * @param array $arr
	 * @return bool|mixed
	 */
	private function _getCleanIPAndServerVar($arr) {
		$privates = array(); //Store private addrs until end as last resort.
		foreach ($arr as $entry) {
			list($item, $var) = $entry;
			if (is_array($item)) {
				foreach ($item as $j) {
					// try verifying the IP is valid before stripping the port off
					if (!$this->_isValidIP($j)) {
						$j = preg_replace('/:\d+$/', '', $j); //Strip off port
					}
					if ($this->_isValidIP($j)) {
						if ($this->_isIPv6MappedIPv4($j)) {
							$j = wfWAFUtils::inet_ntop(wfWAFUtils::inet_pton($j));
						}
						
						if ($this->_isPrivateIP($j)) {
							$privates[] = array($j, $var);
						}
						else {
							return array($j, $var);
						}
					}
				}
				continue; //This was an array so we can skip to the next item
			}
			$skipToNext = false;
			$trustedProxyConfig = wfWAF::getInstance()->getStorageEngine()->getConfig('howGetIPs_trusted_proxies_unified', null, 'synced');
			$trustedProxies = $trustedProxyConfig === null ? array() : explode("\n", $trustedProxyConfig);
			foreach (array(',', ' ', "\t") as $char) {
				if (strpos($item, $char) !== false) {
					$sp = explode($char, $item);
					$sp = array_reverse($sp);
					foreach ($sp as $index => $j) {
						$j = trim($j);
						if (!$this->_isValidIP($j)) {
							$j = preg_replace('/:\d+$/', '', $j); //Strip off port
						}
						if ($this->_isValidIP($j)) {
							if ($this->_isIPv6MappedIPv4($j)) {
								$j = wfWAFUtils::inet_ntop(wfWAFUtils::inet_pton($j));
							}
							
							foreach ($trustedProxies as $proxy) {
								if (!empty($proxy)) {
									if (wfWAFUtils::subnetContainsIP($proxy, $j) && $index < count($sp) - 1) {
										continue 2;
									}
								}
							}
							
							if ($this->_isPrivateIP($j)) {
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
			if ($skipToNext){ continue; } //Skip to next item because this one had a comma, space or tab so was delimited and we didn't find anything.
			
			if (!$this->_isValidIP($item)) {
				$item = preg_replace('/:\d+$/', '', $item); //Strip off port
			}
			if ($this->_isValidIP($item)) {
				if ($this->_isIPv6MappedIPv4($item)) {
					$item = wfWAFUtils::inet_ntop(wfWAFUtils::inet_pton($item));
				}
				
				if ($this->_isPrivateIP($item)) {
					$privates[] = array($item, $var);
				}
				else {
					return array($item, $var);
				}
			}
		}
		if (sizeof($privates) > 0) {
			return $privates[0]; //Return the first private we found so that we respect the order the IP's were passed to this function.
		}
		return false;
	}
	
	/**
	 * @param string $ip
	 * @return bool
	 */
	private function _isValidIP($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}
	
	/**
	 * @param string $ip
	 * @return bool
	 */
	private function _isIPv6MappedIPv4($ip) {
		return preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $ip) > 0;
	}
	
	/**
	 * @param string $addr Should be in dot or colon notation (127.0.0.1 or ::1)
	 * @return bool
	 */
	private function _isPrivateIP($ip) {
		// Run this through the preset list for IPv4 addresses.
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
			$wordfenceLib = realpath(dirname(__FILE__) . '/../lib');
			include($wordfenceLib . '/wfIPWhitelist.php'); // defines $wfIPWhitelist
			$private = $wfIPWhitelist['private'];
			
			foreach ($private as $a) {
				if (wfWAFUtils::subnetContainsIP($a, $ip)) {
					return true;
				}
			}
		}
		
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false
		&& filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
	}
}

class wfWAFWordPressObserver extends wfWAFBaseObserver {

	private $waf;

	public function __construct($waf){
		$this->waf=$waf;
	}

	public function beforeRunRules() {
		// Whitelisted URLs (in WAF config)
		$whitelistedURLs = wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLs', null, 'livewaf');
		if ($whitelistedURLs) {
			$whitelistPattern = "";
			foreach ($whitelistedURLs as $whitelistedURL) {
				$whitelistPattern .= preg_replace('/\\\\\*/', '.*?', preg_quote($whitelistedURL, '/')) . '|';
			}
			$whitelistPattern = '/^(?:' . wfWAFUtils::substr($whitelistPattern, 0, -1) . ')$/i';

			wfWAFRule::create(wfWAF::getInstance(), 0x8000000, 'rule', 'whitelist', 0, 'User Supplied Allowlisted URL', 'allow',
				new wfWAFRuleComparisonGroup(
					new wfWAFRuleComparison(wfWAF::getInstance(), 'match', $whitelistPattern, array(
						'request.uri',
					))
				)
			)->evaluate();
		}

		// Whitelisted IPs (Wordfence config)
		$whitelistedIPs = wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedIPs', null, 'synced');
		if ($whitelistedIPs) {
			if (!is_array($whitelistedIPs)) {
				$whitelistedIPs = explode(',', $whitelistedIPs);
			}
			foreach ($whitelistedIPs as $whitelistedIP) {
				$ipRange = new wfWAFUserIPRange($whitelistedIP);
				if ($ipRange->isIPInRange(wfWAF::getInstance()->getRequest()->getIP())) {
					throw new wfWAFAllowException('Wordfence allowlisted IP.');
				}
			}
		}
		
		// Check plugin blocking
		if ($result = wfWAF::getInstance()->willPerformFinalAction(wfWAF::getInstance()->getRequest())) {
			if ($result === true) { $result = 'Not available'; } // Should not happen but can if the reason in the blocks table is empty
			wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('finalAction' => $result)));
		}
	}
	
	public function afterRunRules()
	{
		//Blacklist
		if (!wfWAF::getInstance()->getStorageEngine()->getConfig('disableWAFBlacklistBlocking')) {
			$blockedPrefixes = wfWAF::getInstance()->getStorageEngine()->getConfig('blockedPrefixes', null, 'transient');
			if ($blockedPrefixes && wfWAF::getInstance()->getStorageEngine()->getConfig('isPaid', null, 'synced')) {
				$blockedPrefixes = base64_decode($blockedPrefixes);
				if ($this->_prefixListContainsIP($blockedPrefixes, wfWAF::getInstance()->getRequest()->getIP()) !== false) {
					$allowedCacheJSON = wfWAF::getInstance()->getStorageEngine()->getConfig('blacklistAllowedCache', '', 'transient');
					$allowedCache = @json_decode($allowedCacheJSON, true);
					if (!is_array($allowedCache)) {
						$allowedCache = array();
					}
					
					$cacheTest = base64_encode(wfWAFUtils::inet_pton(wfWAF::getInstance()->getRequest()->getIP()));
					if (!in_array($cacheTest, $allowedCache)) {
						$guessSiteURL = sprintf('%s://%s/', wfWAF::getInstance()->getRequest()->getProtocol(), wfWAF::getInstance()->getRequest()->getHost());
						try {
							$request = new wfWAFHTTP();
							$response = wfWAFHTTP::get(WFWAF_API_URL_SEC . "?" . http_build_query(array(
									'action' => 'is_ip_blacklisted',
									'ip'	 => wfWAF::getInstance()->getRequest()->getIP(),
									'k'      => wfWAF::getInstance()->getStorageEngine()->getConfig('apiKey', null, 'synced'),
									's'      => wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced') ? wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced') : $guessSiteURL,
									'h'      => wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced') ? wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced') : $guessSiteURL,
									't'		 => microtime(true),
									'lang'   => wfWAF::getInstance()->getStorageEngine()->getConfig('WPLANG', null, 'synced'),
								), '', '&'), $request);
							
							if ($response instanceof wfWAFHTTPResponse && $response->getBody()) {
								$jsonData = wfWAFUtils::json_decode($response->getBody(), true);
								if (is_array($jsonData) && array_key_exists('data', $jsonData)) {
									if (preg_match('/^block:(\d+)$/i', $jsonData['data'], $matches)) {
										wfWAF::getInstance()->getStorageEngine()->blockIP((int)$matches[1] + time(), wfWAF::getInstance()->getRequest()->getIP(), wfWAFStorageInterface::IP_BLOCKS_BLACKLIST);
										$e = new wfWAFBlockException();
										$e->setFailedRules(array('blocked'));
										$e->setRequest(wfWAF::getInstance()->getRequest());
										throw $e;
									}
									else { //Allowed, cache until the next prefix list refresh
										$allowedCache[] = $cacheTest;
										wfWAF::getInstance()->getStorageEngine()->setConfig('blacklistAllowedCache', json_encode($allowedCache), 'transient');
									}
								}
							}
						} catch (wfWAFHTTPTransportException $e) {
							error_log($e->getMessage());
						}
					}
				}
			}
		}
		
		if ($reason = wfWAF::getInstance()->getRequest()->getMetadata('finalAction')) {
			$e = new wfWAFBlockException($reason['action']);
			$e->setRequest(wfWAF::getInstance()->getRequest());
			throw $e;
		}
	}
	
	private function _prefixListContainsIP($prefixList, $ip) {
		$size = ord(wfWAFUtils::substr($prefixList, 0, 1));
		
		$sha256 = hash('sha256', wfWAFUtils::inet_pton($ip), true);
		$p = wfWAFUtils::substr($sha256, 0, $size);
		
		$count = ceil((wfWAFUtils::strlen($prefixList) - 1) / $size);
		$low = 0;
		$high = $count - 1;
		
		while ($low <= $high) {
			$mid = (int) (($high + $low) / 2);
			$val = wfWAFUtils::substr($prefixList, 1 + $mid * $size, $size);
			$cmp = strcmp($val, $p);
			if ($cmp < 0) {
				$low = $mid + 1;
			}
			else if ($cmp > 0) {
				$high = $mid - 1;
			}
			else {
				return $mid;
			}
		}
		
		return false;
	}
}

/**
 *
 */
class wfWAFWordPress extends wfWAF {

	/** @var wfWAFRunException */
	private $learningModeAttackException;

	/**
	 * @param wfWAFBlockException $e
	 * @param int $httpCode
	 */
	public function blockAction($e, $httpCode = 403, $redirect = false, $template = null) {
		$failedRules = $e->getFailedRules();
		if (!is_array($failedRules)) {
			$failedRules = array();
		}
		
		if ($this->isInLearningMode() && !$e->getRequest()->getMetadata('finalAction') && !in_array('blocked', $failedRules)) {
			register_shutdown_function(array(
				$this, 'whitelistFailedRulesIfNot404',
			));
			$this->getStorageEngine()->logAttack($e->getFailedRules(), $e->getParamKey(), $e->getParamValue(), $e->getRequest());
			$this->setLearningModeAttackException($e);
		} else {
			if (empty($failedRules)) {
				$finalAction = $e->getRequest()->getMetadata('finalAction');
				if (is_array($finalAction)) {
					$isLockedOut = isset($finalAction['lockout']) && $finalAction['lockout'];
					$finalAction = $finalAction['action'];
					if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_REDIR)) {
						$redirect = wfWAFIPBlocksController::currentController()->countryRedirURL();
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR)) {
						$redirect = wfWAFIPBlocksController::currentController()->countryBypassRedirURL();
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_UAREFIPRANGE)) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => 'Advanced blocking in effect.', '503Time' => 3600)));
						$httpCode = 503;
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY)) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => 'Access from your area has been temporarily limited for security reasons.', '503Time' => 3600)));
						$httpCode = 503;
					}
					else if (is_string($finalAction) && strlen($finalAction) > 0) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => $finalAction, '503Time' => 3600)));
						$httpCode = 503;
						
						if ($isLockedOut) {
							parent::blockAction($e, $httpCode, $redirect, '503-lockout'); //exits
						}
					}
				}
			}
			else if (array_search('blocked', $failedRules) !== false) {
				parent::blockAction($e, $httpCode, $redirect, '403-blacklist'); //exits
			}
			
			parent::blockAction($e, $httpCode, $redirect, $template);
		}
	}

	/**
	 * @param wfWAFBlockXSSException $e
	 * @param int $httpCode
	 */
	public function blockXSSAction($e, $httpCode = 403, $redirect = false) {
		if ($this->isInLearningMode() && !$e->getRequest()->getMetadata('finalAction')) {
			register_shutdown_function(array(
				$this, 'whitelistFailedRulesIfNot404',
			));
			$this->getStorageEngine()->logAttack($e->getFailedRules(), $e->getParamKey(), $e->getParamValue(), $e->getRequest());
			$this->setLearningModeAttackException($e);
		} else {
			$failedRules = $e->getFailedRules();
			if (empty($failedRules)) {
				$finalAction = $e->getRequest()->getMetadata('finalAction');
				if (is_array($finalAction)) {
					$finalAction = $finalAction['action'];
					if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_REDIR)) {
						$redirect = wfWAFIPBlocksController::currentController()->countryRedirURL();
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR)) {
						$redirect = wfWAFIPBlocksController::currentController()->countryBypassRedirURL();
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_UAREFIPRANGE)) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => 'Advanced blocking in effect.', '503Time' => 3600)));
						$httpCode = 503;
					}
					else if (wfWAFBlockI18n::matchesBlockKey($finalAction, wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY)) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => 'Access from your area has been temporarily limited for security reasons.', '503Time' => 3600)));
						$httpCode = 503;
					}
					else if (is_string($finalAction) && strlen($finalAction) > 0) {
						wfWAF::getInstance()->getRequest()->setMetadata(array_merge(wfWAF::getInstance()->getRequest()->getMetadata(), array('503Reason' => $finalAction, '503Time' => 3600)));
						$httpCode = 503;
					}
				}
			}
			
			parent::blockXSSAction($e, $httpCode, $redirect);
		}
	}

	private function isCli() {
		return (php_sapi_name()==='cli') || !array_key_exists('REQUEST_METHOD', $_SERVER);
	}

	/**
	 *
	 */
	public function runCron() {
		if($this->isCli()){
			return;
		}
		/**
		 * Removed sending attack data. Attack data is sent in @see wordfence::veryFirstAction
		 */
		$storage = $this->getStorageEngine();
		$cron = (array) $storage->getConfig('cron', null, 'livewaf');
		$run = array();
		$updated = false;
		if (is_array($cron)) {
			/** @var wfWAFCronEvent $event */
			$cronDeduplication = array();
			foreach ($cron as $index => $event) {
				if (is_object($event) && $event instanceof wfWAFCronEvent) {
					$event->setWaf($this);
					if ($event->isInPast()) {
						$run[$index] = $event;
						$newEvent = $event->reschedule();
						$className = is_object($newEvent) ? get_class($newEvent) : null;
						if ($newEvent && $newEvent instanceof wfWAFCronEvent && $newEvent !== $event && !in_array($className, $cronDeduplication)) {
							$cron[$index] = $newEvent;
							$cronDeduplication[] = $className;
							$updated = true;
						} else {
							unset($cron[$index]);
							$updated = true;
						}
					}
					else {
						$className = get_class($event);
						if (in_array($className, $cronDeduplication)) {
							unset($cron[$index]);
							$updated = true;
						}
						else {
							$cronDeduplication[] = $className;
						}
					}
				}
				else { //Remove bad/corrupt records
					unset($cron[$index]);
					$updated = true;
				}
			}
		}
		$storage->setConfig('cron', $cron, 'livewaf');
		
		if ($updated && method_exists($storage, 'saveConfig')) {
			$storage->saveConfig('livewaf');
		}
		
		foreach ($run as $index => $event) {
			$event->fire();
		}
	}

	/**
	 *
	 */
	public function whitelistFailedRulesIfNot404() {
		/** @var WP_Query $wp_query */
		global $wp_query;
		if (defined('ABSPATH') &&
			isset($wp_query) && class_exists('WP_Query') && $wp_query instanceof WP_Query &&
			method_exists($wp_query, 'is_404') && $wp_query->is_404() &&
			function_exists('is_admin') && !is_admin()) {
			return;
		}
		$this->whitelistFailedRules();
	}

	/**
	 * @param $ip
	 * @return mixed
	 */
	public function isIPBlocked($ip) {
		return parent::isIPBlocked($ip);
	}
	
	/**
	 * @param wfWAFRequest $request
	 * @return bool|string false if it should not be blocked, otherwise true or a reason for blocking 
	 */
	public function willPerformFinalAction($request) {
		try {
			$disableWAFIPBlocking = $this->getStorageEngine()->getConfig('disableWAFIPBlocking', null, 'synced');
			$advancedBlockingEnabled = $this->getStorageEngine()->getConfig('advancedBlockingEnabled', null, 'synced');
		}
		catch (Exception $e) {
			return false;
		}
		
		if ($disableWAFIPBlocking || !$advancedBlockingEnabled) {
			return false;
		}
		
		return wfWAFIPBlocksController::currentController()->shouldBlockRequest($request);
	}
	
	public function uninstall() {
		parent::uninstall();
		@unlink(rtrim(WFWAF_LOG_PATH, '/') . '/.htaccess');
		@unlink(rtrim(WFWAF_LOG_PATH, '/') . '/template.php');
		@unlink(rtrim(WFWAF_LOG_PATH, '/') . '/geoip.mmdb');
		
		self::_recursivelyRemoveWflogs(''); //Removes any remaining files and the directory itself
	}
	
	/**
	 * Removes a path within wflogs, recursing as necessary.
	 *
	 * @param string $file
	 * @param array $processedDirs
	 * @return array The list of removed files/folders.
	 */
	private static function _recursivelyRemoveWflogs($file, $processedDirs = array()) {
		if (preg_match('~(?:^|/|\\\\)\.\.(?:/|\\\\|$)~', $file)) {
			return array();
		}
		
		if (stripos(WFWAF_LOG_PATH, 'wflogs') === false) { //Sanity check -- if not in a wflogs folder, user will have to do removal manually
			return array();
		}
		
		$path = rtrim(WFWAF_LOG_PATH, '/') . '/' . $file;
		if (is_link($path)) {
			if (@unlink($path)) {
				return array($file);
			}
			return array();
		}
		
		if (is_dir($path)) {
			$real = realpath($file);
			if (in_array($real, $processedDirs)) {
				return array();
			}
			$processedDirs[] = $real;
			
			$count = 0;
			$dir = opendir($path);
			if ($dir) {
				$contents = array();
				while ($sub = readdir($dir)) {
					if ($sub == '.' || $sub == '..') { continue; }
					$contents[] = $sub;
				}
				closedir($dir);
				
				$filesRemoved = array();
				foreach ($contents as $f) {
					$removed = self::_recursivelyRemoveWflogs($file . '/' . $f, $processedDirs);
					$filesRemoved = array($filesRemoved, $removed);
				}
			}
			
			if (@rmdir($path)) {
				$filesRemoved[] = $file;
			}
			return $filesRemoved;
		}
		
		if (@unlink($path)) {
			return array($file);
		}
		return array();
	}
	
	public function fileList() {
		$fileList = parent::fileList();
		$fileList[] = rtrim(WFWAF_LOG_PATH, '/') . '/.htaccess';
		$fileList[] = rtrim(WFWAF_LOG_PATH, '/') . '/template.php';
		$fileList[] = rtrim(WFWAF_LOG_PATH, '/') . '/geoip.mmdb';
		return $fileList;
	}

	/**
	 * @return wfWAFRunException
	 */
	public function getLearningModeAttackException() {
		return $this->learningModeAttackException;
	}

	/**
	 * @param wfWAFRunException $learningModeAttackException
	 */
	public function setLearningModeAttackException($learningModeAttackException) {
		$this->learningModeAttackException = $learningModeAttackException;
	}
	
	public static function permissions() {
		if (defined('WFWAF_LOG_FILE_MODE')) {
			return WFWAF_LOG_FILE_MODE;
		}
		
		if (class_exists('wfWAFStorageFile') && method_exists('wfWAFStorageFile', 'permissions')) {
			return wfWAFStorageFile::permissions();
		}
		
		static $_cachedPermissions = null;
		if ($_cachedPermissions === null) {
			if (defined('WFWAF_LOG_PATH')) {
				$template = rtrim(WFWAF_LOG_PATH . '/') . '/template.php';
				if (file_exists($template)) {
					$stat = @stat($template);
					if ($stat !== false) {
						$mode = $stat[2];
						$updatedMode = 0600;
						if (($mode & 0020) == 0020) {
							$updatedMode = $updatedMode | 0060;
						}
						$_cachedPermissions = $updatedMode;
						return $updatedMode;
					}
				}
			}
			return 0660;
		}
		return $_cachedPermissions;
	}
	
	public static function writeHtaccess() {
		@file_put_contents(rtrim(WFWAF_LOG_PATH, '/') . '/.htaccess', <<<APACHE
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
APACHE
		);
		@chmod(rtrim(WFWAF_LOG_PATH, '/') . '/.htaccess', (wfWAFWordPress::permissions() | 0444));
	}

	public function getGlobal($global) {
		if (wfWAFUtils::strpos($global, '.') === false) {
			return null;
		}
		list($prefix, $_global) = explode('.', $global);
		switch ($prefix) {
			case 'wordpress':
				if ($_global === 'core') {
					return $this->getStorageEngine()->getConfig('wordpressVersion', null, 'synced');
				} else if ($_global === 'plugins') {
					return $this->getStorageEngine()->getConfig('wordpressPluginVersions', null, 'synced');
				} else if ($_global === 'themes') {
					return $this->getStorageEngine()->getConfig('wordpressThemeVersions', null, 'synced');
				}
				break;
		}
		return parent::getGlobal($global);
	}
}

class wfWAFWordPressStorageMySQL extends wfWAFStorageMySQL {

	public function getSerializedParams() {
		$params = parent::getSerializedParams();
		$params[] = 'wordpressPluginVersions';
		$params[] = 'wordpressThemeVersions';
		return $params;
	}

	public function getAutoloadParams() {
		$params = parent::getAutoloadParams();
		$params['synced'][] = 'wordpressVersion';
		$params['synced'][] = 'wordpressPluginVersions';
		$params['synced'][] = 'wordpressThemeVersions';
		return $params;
	}
}

class wfWAFWordPressI18n implements wfWAFI18nEngine {

	protected $translations;

	/** @var wfWAFStorageInterface */
	private $storageEngine;
	/**
	 * @var wfMO
	 */
	private $mo;

	/**
	 * @param wfWAFStorageInterface $storageEngine
	 */
	public function __construct($storageEngine) {
		$this->storageEngine = $storageEngine;
		$this->loadTranslations();
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function __($text) {
		if (!$this->storageEngine->getConfig('wordfenceI18n', true, 'synced')) {
			return $text;
		}

		if ($this->mo) {
			$translated = $this->mo->translate($text);
			if ($translated) {
				return $translated;
			}
		}

		return $text;
	}

	private function getPotentialDirectories() {
		return array(
			dirname(__FILE__) . "/../languages/",
			dirname(WFWAF_LOG_PATH) . "/languages/plugins/"
		);
	}

	protected function loadTranslations() {
		require_once dirname(__FILE__) . '/pomo/mo.php';

		$currentLocale = $this->storageEngine->getConfig('WPLANG', '', 'synced');
		if (!preg_match("/^[a-zA-Z_]+$/", $currentLocale))
			return false;
		$filename = "wordfence-{$currentLocale}.mo";

		foreach ($this->getPotentialDirectories() as $directory) {
			$path = "{$directory}/{$filename}";
			if (file_exists($path)) {
				$this->mo = new wfMO();
				return $this->mo->import_from_file($path);
			}
		}
		return false;
	}
}

try {

if (!defined('WFWAF_LOG_PATH')) {
	if (!defined('WP_CONTENT_DIR')) { //Loading before WordPress
		exit();
	}
	define('WFWAF_LOG_PATH', WP_CONTENT_DIR . '/wflogs/');
}
if (!is_dir(WFWAF_LOG_PATH)) {
	@mkdir(WFWAF_LOG_PATH, (wfWAFWordPress::permissions() | 0755));
	@chmod(WFWAF_LOG_PATH, (wfWAFWordPress::permissions() | 0755));
	wfWAFWordPress::writeHtaccess();
}


try {
	if (!defined('WFWAF_STORAGE_ENGINE') && isset($_SERVER['WFWAF_STORAGE_ENGINE'])) {
		define('WFWAF_STORAGE_ENGINE', $_SERVER['WFWAF_STORAGE_ENGINE']);
	}
	else if (!defined('WFWAF_STORAGE_ENGINE') && (WF_IS_WP_ENGINE || WF_IS_FLYWHEEL)) {
		define('WFWAF_STORAGE_ENGINE', 'mysqli');
	}

	$specifiedStorageEngine = defined('WFWAF_STORAGE_ENGINE');
	$fallbackStorageEngine = false;
	if ($specifiedStorageEngine) {
		switch (WFWAF_STORAGE_ENGINE) {
			case 'mysqli':
				$wfWAFDBCredentials = array();
				$sslOptions = array();
				$overrideConstants = array(
					'wfWAFDBCredentials' => array(
						'WFWAF_DB_NAME' => 'database',
						'WFWAF_DB_USER' => 'user',
						'WFWAF_DB_PASSWORD' => 'pass',
						'WFWAF_DB_HOST' => 'host',
						'WFWAF_DB_CHARSET' => 'charset',
						'WFWAF_DB_COLLATE' => 'collation',
						'WFWAF_MYSQL_CLIENT_FLAGS' => 'flags',
						'WFWAF_TABLE_PREFIX' => 'tablePrefix'
					),
					'sslOptions' => array(
						'WFWAF_DB_SSL_KEY' => 'key',
						'WFWAF_DB_SSL_CERTIFICATE' => 'certificate',
						'WFWAF_DB_SSL_CA_CERTIFICATE' => 'ca_certificate',
						'WFWAF_DB_SSL_CA_PATH' => 'ca_path',
						'WFWAF_DB_SSL_CIPHER_ALGOS' => 'cipher_algos'
					)
				);
				foreach ($overrideConstants as $variable => $constants) {
					foreach ($constants as $constant => $key) {
						if (defined($constant)) {
							${$variable}[$key] = constant($constant);
						}
					}
				}

				// Find the wp-config.php
				if (is_dir(dirname(WFWAF_LOG_PATH))) {
					if (file_exists(dirname(WFWAF_LOG_PATH) . '/../wp-config.php')) {
						wfWAFUtils::extractCredentialsWPConfig(dirname(WFWAF_LOG_PATH) . '/../wp-config.php', $wfWAFDBCredentials);
					} else if (file_exists(dirname(WFWAF_LOG_PATH) . '/../../wp-config.php')) {
						wfWAFUtils::extractCredentialsWPConfig(dirname(WFWAF_LOG_PATH) . '/../../wp-config.php', $wfWAFDBCredentials);
					}
				} else if (!empty($_SERVER['DOCUMENT_ROOT'])) {
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php')) {
						wfWAFUtils::extractCredentialsWPConfig($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php', $wfWAFDBCredentials);
					} else if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/../wp-config.php')) {
						wfWAFUtils::extractCredentialsWPConfig($_SERVER['DOCUMENT_ROOT'] . '/../wp-config.php', $wfWAFDBCredentials);
					}
				} else {
					$wfWAFDBCredentials = false;
				}

				if (!empty($wfWAFDBCredentials)) {
					$wfWAFStorageEngine = new wfWAFWordPressStorageMySQL(new wfWAFStorageEngineMySQLi(), $wfWAFDBCredentials['tablePrefix'], wfShutdownRegistry::getDefaultInstance());
					$wfWAFStorageEngine->getDb()->connect(
						$wfWAFDBCredentials['user'],
						$wfWAFDBCredentials['pass'],
						$wfWAFDBCredentials['database'],
						!empty($wfWAFDBCredentials['ipv6']) ? '[' . $wfWAFDBCredentials['host'] . ']' : $wfWAFDBCredentials['host'],
						!empty($wfWAFDBCredentials['port']) ? $wfWAFDBCredentials['port'] : null,
						!empty($wfWAFDBCredentials['socket']) ? $wfWAFDBCredentials['socket'] : null,
						array_key_exists('flags', $wfWAFDBCredentials) ? $wfWAFDBCredentials['flags'] : 0,
						$sslOptions
					);
					if (array_key_exists('charset', $wfWAFDBCredentials)) {
						$wfWAFStorageEngine->getDb()
							->setCharset($wfWAFDBCredentials['charset'],
								!empty($wfWAFDBCredentials['collation']) ? $wfWAFDBCredentials['collation'] : '');
					}
					if (defined('ABSPATH')) {
						$tableExists = false;
						$optionName = 'wordfence_installed'; //Also exists in wfConfig.php
						if (is_multisite() && function_exists('get_network_option')) {
							$tableExists = get_network_option(null, $optionName, null);
						}
						else {
							$tableExists = get_option($optionName, null);
						}
						
						$wfWAFStorageEngine->installing = !$tableExists;
						$wfWAFStorageEngine->getDb()->installing = $wfWAFStorageEngine->installing;
					}

				} else {
					unset($wfWAFDBCredentials);
				}

				break;
		}
	}

	if (empty($wfWAFStorageEngine)) {
		$wfWAFStorageEngine = new wfWAFStorageFile(
			WFWAF_LOG_PATH . 'attack-data.php',
			WFWAF_LOG_PATH . 'ips.php',
			WFWAF_LOG_PATH . 'config.php',
			WFWAF_LOG_PATH . 'rules.php',
			WFWAF_LOG_PATH . 'wafRules.rules'
		);
		if ($specifiedStorageEngine)
			$fallbackStorageEngine = true;
	}

	wfWAF::setSharedStorageEngine($wfWAFStorageEngine, $fallbackStorageEngine);
	wfWAF::setInstance(new wfWAFWordPress(wfWAFWordPressRequest::createFromGlobals(), wfWAF::getSharedStorageEngine()));
	wfWAF::getInstance()->getEventBus()->attach(new wfWAFWordPressObserver(wfWAF::getInstance()));

	if ($wfWAFStorageEngine instanceof wfWAFStorageFile) {
		$rulesFiles = array(
			WFWAF_LOG_PATH . 'rules.php',
			// WFWAF_PATH . 'rules.php',
		);
		foreach ($rulesFiles as $rulesFile) {
			if (!file_exists($rulesFile) && !wfWAF::getInstance()->isReadOnly()) {
				@touch($rulesFile);
			}
			@chmod($rulesFile, (wfWAFWordPress::permissions() | 0444));
			if (is_writable($rulesFile)) {
				wfWAF::getInstance()->setCompiledRulesFile($rulesFile);
				break;
			}
		}
	} else if ($wfWAFStorageEngine instanceof wfWAFStorageMySQL) {
		$wfWAFStorageEngine->runMigrations();
		$wfWAFStorageEngine->setDefaults();
	}

	if (!wfWAF::getInstance()->isReadOnly()) {
		if (wfWAF::getInstance()->getStorageEngine()->needsInitialRules()) {
			try {
				if (wfWAF::getInstance()->getStorageEngine()->getConfig('apiKey', null, 'synced') !== null &&
					wfWAF::getInstance()->getStorageEngine()->getConfig('createInitialRulesDelay', null, 'transient') < time()
				) {
					$event = new wfWAFCronFetchRulesEvent(time() - 60);
					$event->setWaf(wfWAF::getInstance());
					$event->fire();
					wfWAF::getInstance()->getStorageEngine()->setConfig('createInitialRulesDelay', time() + (5 * 60), 'transient');
				}
			} catch (wfWAFBuildRulesException $e) {
				// Log this somewhere
				error_log($e->getMessage());
			} catch (Exception $e) {
				// Suppress this
				error_log($e->getMessage());
			}
		}
	}

	if (WFWAF_DEBUG && file_exists(wfWAF::getInstance()->getStorageEngine()->getRulesDSLCacheFile())) {
		try {
			wfWAF::getInstance()->updateRuleSet(file_get_contents(wfWAF::getInstance()->getStorageEngine()->getRulesDSLCacheFile()), false);
		} catch (wfWAFBuildRulesException $e) {
			$GLOBALS['wfWAFDebugBuildException'] = $e;
		} catch (Exception $e) {
			$GLOBALS['wfWAFDebugBuildException'] = $e;
		}
	}

	wfWAFI18n::setInstance(new wfWAFI18n(new wfWAFWordPressI18n($wfWAFStorageEngine)));

	try {
		wfWAF::getInstance()->run();
	} catch (wfWAFBuildRulesException $e) {
		// Log this
		error_log($e->getMessage());
	}

} catch (wfWAFStorageFileConfigException $e) {
	// Let this request through for now
	error_log($e->getMessage());

} catch (wfWAFStorageEngineMySQLiException $e) {
	// Let this request through for now
	error_log($e->getMessage());

} catch (wfWAFStorageFileException $e) {
	// We need to choose another storage engine here.
}

}
catch (Exception $e) { // In PHP 5, Throwable does not exist
	error_log("An unexpected exception occurred during WAF execution: {$e}");
	$wf_waf_failure = array(
		'throwable' => $e
	);
}
catch (Throwable $t) {
	error_log("An unexpected exception occurred during WAF execution: {$t}");
	if (class_exists('ParseError') && $t instanceof ParseError) {
		//Do nothing
	}
	else {
		$wf_waf_failure = array(
			'throwable' => $t
		);
	}
}
if (wfWAF::getInstance() === null) {
	require_once __DIR__ . '/dummy.php';
	wfWAF::setInstance(new wfDummyWaf());
}
define('WFWAF_RUN_COMPLETE', true);
}