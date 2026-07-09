<?php
require_once(dirname(__FILE__) . '/wfUtils.php');
class wfCrawl {
	const GOOGLE_BOT_VERIFIED = 'verified';
	const GOOGLE_BOT_FAKE = 'fakeBot';
	const GOOGLE_BOT_UNDETERMINED = 'undetermined';
	
	public static function isCrawler($UA){
		$browscap = new wfBrowscap();
		$b = $browscap->getBrowser($UA);
		if (!$b || $b['Parent'] == 'DefaultProperties') {
			$IP = wfUtils::getIP(); 
			return !wfLog::isHumanRequest($IP, $UA);
		}
		else if (isset($b['Crawler']) && $b['Crawler']) {
			return true;
		}
		
		return false;
	}
	public static function verifyCrawlerPTR($hostPattern, $IP){
		$table = wfDB::networkTable('wfCrawlers');
		$db = new wfDB();
		$IPn = wfUtils::inet_pton($IP);
		$ipHex = wfDB::binaryValueToSQLHex($IPn);
		$status = $db->querySingle("select status from $table where IP={$ipHex} and patternSig=UNHEX(MD5('%s')) and lastUpdate > unix_timestamp() - %d", $hostPattern, WORDFENCE_CRAWLER_VERIFY_CACHE_TIME);
		if($status){
			if($status == 'verified'){
				return true;
			} else {
				return false;
			}
		}
		$host = wfUtils::reverseLookup($IP);
		if(! $host){ 
			$db->queryWrite("insert into $table (IP, patternSig, status, lastUpdate, PTR) values ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp(), '%s') ON DUPLICATE KEY UPDATE status='%s', lastUpdate=unix_timestamp(), PTR='%s'", $hostPattern, 'noPTR', '', 'noPTR', '');
			return false; 
		}
		if(preg_match($hostPattern, $host)){
			$resultIPs = wfUtils::resolveDomainName($host);
			$addrsMatch = false;
			foreach($resultIPs as $resultIP){
				if($resultIP == $IP){
					$addrsMatch = true;
					break;
				}
			}
			if($addrsMatch){
				$db->queryWrite("insert into $table (IP, patternSig, status, lastUpdate, PTR) values ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp(), '%s') ON DUPLICATE KEY UPDATE status='%s', lastUpdate=unix_timestamp(), PTR='%s'", $hostPattern, 'verified', $host, 'verified', $host);
				return true;
			} else {
				$db->queryWrite("insert into $table (IP, patternSig, status, lastUpdate, PTR) values ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp(), '%s') ON DUPLICATE KEY UPDATE status='%s', lastUpdate=unix_timestamp(), PTR='%s'", $hostPattern, 'fwdFail', $host, 'fwdFail', $host);
				return false;
			}
		} else {
			$db->queryWrite("insert into $table (IP, patternSig, status, lastUpdate, PTR) values ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp(), '%s') ON DUPLICATE KEY UPDATE status='%s', lastUpdate=unix_timestamp(), PTR='%s'", $hostPattern, 'badPTR', $host, 'badPTR', $host);
			return false;
		}
	}
	public static function isGooglebot($userAgent = null){
		if ($userAgent === null) {
			$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		}
		return (bool) preg_match('/Googlebot\/\d\.\d/', $userAgent);
	}
	public static function isGoogleCrawler($userAgent = null){
		if ($userAgent === null) {
			$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		}
		foreach (self::$googPat as $pat) {
			if (preg_match($pat . 'i', $userAgent)) {
				return true;
			}
		}
		return false;
	}
	private static $googPat = array(
'@^Mozilla/5\\.0 \\(.*Google Keyword Tool.*\\)$@',
'@^Mozilla/5\\.0 \\(.*Feedfetcher\\-Google.*\\)$@',
'@^Feedfetcher\\-Google\\-iGoogleGadgets.*$@',
'@^searchbot admin\\@google\\.com$@',
'@^Google\\-Site\\-Verification.*$@',
'@^Google OpenSocial agent.*$@',
'@^.*Googlebot\\-Mobile/2\\..*$@',
'@^AdsBot\\-Google\\-Mobile.*$@',
'@^google \\(.*Enterprise.*\\)$@',
'@^Mediapartners\\-Google.*$@',
'@^GoogleFriendConnect.*$@',
'@^googlebot\\-urlconsole$@',
'@^.*Google Web Preview.*$@',
'@^Feedfetcher\\-Google.*$@',
'@^AppEngine\\-Google.*$@',
'@^Googlebot\\-Video.*$@',
'@^Googlebot\\-Image.*$@',
'@^Google\\-Sitemaps.*$@',
'@^Googlebot/Test.*$@',
'@^Googlebot\\-News.*$@',
'@^.*Googlebot/2\\.1.*$@',
'@^AdsBot\\-Google.*$@',
'@^Google$@'
	);


	/**
	 * Has correct user agent and PTR record points to .googlebot.com domain.
	 *
	 * @param string|null $ip
	 * @param string|null $ua
	 * @return bool
	 */
	public static function isVerifiedGoogleCrawler($ip = null, $ua = null) {
		static $verified;
		if (!isset($verified)) {
			$verified = array();
		}
		if ($ip === null) {
			$ip = wfUtils::getIP();
		}
		
		if ($ip === null || $ip === false) { //Likely a CLI execution
			return false;
		}
		
		if (array_key_exists($ip, $verified)) {
			return $verified[$ip];
		}
		if (self::isGoogleCrawler($ua)) {
			$services = wfUtils::whitelistPresets();
			if (array_key_exists('google', $services)) {
				$ranges = $services['google']['r'];
				foreach ($ranges as $r) {
					if (wfUtils::subnetContainsIP($r, $ip)) {
						$verified[$ip] = true;
						return $verified[$ip];
					}
				}
			}
			
			if (self::verifyCrawlerPTR(wordfence::getLog()->getGooglePattern(), $ip)) {
				$verified[$ip] = true;
				return $verified[$ip];
			}
			
			$noc1Status = self::verifyGooglebotViaNOC1($ip);
			if ($noc1Status == self::GOOGLE_BOT_VERIFIED) {
				$verified[$ip] = true;
				return $verified[$ip];
			}
			else if ($noc1Status == self::GOOGLE_BOT_FAKE) {
				$verified[$ip] = false;
				return $verified[$ip];
			}
			
			if (!array_key_exists('google', $services)) {
				return true; //We were unable to successfully validate Googlebot status so default to being permissive if the service IP list is missing
			}
		}
		$verified[$ip] = false;
		return $verified[$ip];
	}

	/**
	 * Attempts to verify whether an IP claiming to be Googlebot is actually Googlebot.
	 * 
	 * @param string|null $ip
	 * @return string
	 */
	public static function verifyGooglebotViaNOC1($ip = null) {
		$table = wfDB::networkTable('wfCrawlers');
		if ($ip === null) {
			$ip = wfUtils::getIP();
		}
		$db = new wfDB();
		$IPn = wfUtils::inet_pton($ip);
		$ipHex = wfDB::binaryValueToSQLHex($IPn);
		$patternSig = 'googlenoc1';
		$status = $db->querySingle("select status from $table
				where IP={$ipHex}
				and patternSig=UNHEX(MD5('%s'))
				and lastUpdate > unix_timestamp() - %d",
				$patternSig,
				WORDFENCE_CRAWLER_VERIFY_CACHE_TIME);
		if ($status === 'verified') {
			return self::GOOGLE_BOT_VERIFIED;
		} else if ($status === 'fakeBot') {
			return self::GOOGLE_BOT_FAKE;
		}

		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$data = $api->call('verify_googlebot', array(
				'ip' => $ip,
			));
			if (is_array($data) && !empty($data['verified'])) {
				// Cache results
				$db->queryWrite("INSERT INTO {$table} (IP, patternSig, status, lastUpdate) VALUES ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp()) ON DUPLICATE KEY UPDATE status = VALUES(status), lastUpdate = VALUES(lastUpdate)", $patternSig, 'verified');
				return self::GOOGLE_BOT_VERIFIED;
			} else {
				$db->queryWrite("INSERT INTO {$table} (IP, patternSig, status, lastUpdate) VALUES ({$ipHex}, UNHEX(MD5('%s')), '%s', unix_timestamp()) ON DUPLICATE KEY UPDATE status = VALUES(status), lastUpdate = VALUES(lastUpdate)", $patternSig, 'fakeBot');
				self::GOOGLE_BOT_FAKE;
			}
		} catch (Exception $e) {
			// Do nothing, bail
		}
		return self::GOOGLE_BOT_UNDETERMINED;
	}
}
