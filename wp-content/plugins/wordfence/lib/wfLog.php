<?php
require_once(dirname(__FILE__) . '/wfDB.php');
require_once(dirname(__FILE__) . '/wfUtils.php');
require_once(dirname(__FILE__) . '/wfBrowscap.php');
class wfLog {
	public $canLogHit = true;
	private $effectiveUserID = 0;
	private $hitsTable = '';
	private $apiKey = '';
	private $wp_version = '';
	private $db = false;
	private $googlePattern = '/\.(?:googlebot\.com|google\.[a-z]{2,3}|google\.[a-z]{2}\.[a-z]{2}|1e100\.net)$/i';
	private $loginsTable, $statusTable;
	private static $gbSafeCache = array();

	/**
	 * @var wfRequestModel
	 */
	private $currentRequest;
	
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		}
		return $_shared;
	}
	
	/**
	 * Returns whether or not we have a cached record identifying the visitor as human. This is used both by certain
	 * rate limiting features and by Live Traffic.
	 * 
	 * @param bool|string $IP
	 * @param bool|string $UA
	 * @return bool
	 */
	public static function isHumanRequest($IP = false, $UA = false) {
		global $wpdb;
		
		if ($IP === false) {
			$IP = wfUtils::getIP();
		}
		
		if ($UA === false || $UA === null) {
			$UA = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		}
		
		$ipHex = wfDB::binaryValueToSQLHex(wfUtils::inet_pton($IP));
		$table = wfDB::networkTable('wfLiveTrafficHuman');
		if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE IP = {$ipHex} AND identifier = %s AND expiration >= UNIX_TIMESTAMP()", hash('sha256', $UA, true)))) {
			return true;
		}
		return false;
	}
	
	/**
	 * Creates a cache record for the requester to tag it as human.
	 * 
	 * @param bool|string $IP
	 * @param bool|string $UA
	 * @return bool
	 */
	public static function cacheHumanRequester($IP = false, $UA = false) {
		global $wpdb;
		
		if ($IP === false) {
			$IP = wfUtils::getIP();
		}
		
		if ($UA === false) {
			$UA = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		}
		
		$ipHex = wfDB::binaryValueToSQLHex(wfUtils::inet_pton($IP));
		$table = wfDB::networkTable('wfLiveTrafficHuman');
		if ($wpdb->get_var($wpdb->prepare("INSERT IGNORE INTO {$table} (IP, identifier, expiration) VALUES ({$ipHex}, %s, UNIX_TIMESTAMP() + 86400)", hash('sha256', $UA, true)))) {
			return true;
		}
	}
	
	/**
	 * Prunes any expired records from the human cache.
	 */
	public static function trimHumanCache() {
		global $wpdb;
		$table = wfDB::networkTable('wfLiveTrafficHuman');
		$wpdb->query("DELETE FROM {$table} WHERE `expiration` < UNIX_TIMESTAMP()");
	}

	public function __construct($apiKey, $wp_version){
		$this->apiKey = $apiKey;
		$this->wp_version = $wp_version;
		$this->hitsTable = wfDB::networkTable('wfHits');
		$this->loginsTable = wfDB::networkTable('wfLogins');
		$this->statusTable = wfDB::networkTable('wfStatus');
		
		add_filter('determine_current_user', array($this, '_userIDDetermined'), 99, 1);
	}
	
	public function _userIDDetermined($userID) {
		//Needed because the REST API will clear the authenticated user if it fails a nonce check on the request
		$this->effectiveUserID = (int) $userID;
		return $userID;
	}

	public function initLogRequest() {
		if ($this->currentRequest === null) {
			$this->currentRequest = new wfRequestModel();

			$this->currentRequest->ctime = sprintf('%.6f', microtime(true));
			$this->currentRequest->statusCode = 200;
			$this->currentRequest->isGoogle = (wfCrawl::isGoogleCrawler() ? 1 : 0);
			$this->currentRequest->IP = wfUtils::inet_pton(wfUtils::getIP());
			$this->currentRequest->userID = $this->getCurrentUserID();
			$this->currentRequest->URL = wfUtils::getRequestedURL();
			$this->currentRequest->referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
			$this->currentRequest->UA = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
			$this->currentRequest->jsRun = 0;
			
			add_action('wp_loaded', array($this, 'actionSetRequestJSEnabled'));
			add_action('init', array($this, 'actionSetRequestOnInit'), 9999);

			if (function_exists('register_shutdown_function')) {
				register_shutdown_function(array($this, 'logHit'));
			}
		}
	}

	public function actionSetRequestJSEnabled() {
		if (get_current_user_id() > 0) {
			$this->currentRequest->jsRun = true;
			return;
		}
		
		$IP = wfUtils::getIP();
		$UA = $this->currentRequest->UA;
		$this->currentRequest->jsRun = wfLog::isHumanRequest($IP, $UA);
	}

	/**
	 * CloudFlare's plugin changes $_SERVER['REMOTE_ADDR'] on init.
	 */
	public function actionSetRequestOnInit() {
		$this->currentRequest->IP = wfUtils::inet_pton(wfUtils::getIP());
		$this->currentRequest->userID = $this->getCurrentUserID();
	}

	/**
	 * @return wfRequestModel
	 */
	public function getCurrentRequest() {
		return $this->currentRequest;
	}
	
	public function logLogin($action, $fail, $username){
		if(! $username){
			return;
		}
		$user = get_user_by('login', $username);
		$userID = 0;
		if($user){
			$userID = $user->ID;
			if(! $userID){
				return;
			}
		}
		else {
			$user = get_user_by('email', $username);
			if ($user) {
				$userID = $user->ID;
				if (!$userID) {
					return;
				}
			}
		}
		// change the action flag here if the user does not exist.
		if ($action == 'loginFailValidUsername' && $userID == 0) {
			$action = 'loginFailInvalidUsername';
		}

		$hitID = 0;
		if ($this->currentRequest !== null) {
			$this->currentRequest->userID = $userID;
			$this->currentRequest->action = $action;
			$this->currentRequest->save();
			$hitID = $this->currentRequest->getPrimaryKey();
		}

		//Else userID stays 0 but we do log this even though the user doesn't exist.
		$ipHex = wfDB::binaryValueToSQLHex(wfUtils::inet_pton(wfUtils::getIP()));
		$this->getDB()->queryWrite("insert into " . $this->loginsTable . " (hitID, ctime, fail, action, username, userID, IP, UA) values (%d, %f, %d, '%s', '%s', %s, {$ipHex}, '%s')",
			$hitID,
			sprintf('%.6f', microtime(true)),
			$fail,
			$action,
			$username,
			$userID,
			(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
			);
	}
	private function getCurrentUserID(){
		if (!function_exists('get_current_user_id') || !defined('AUTH_COOKIE')) { //If pluggable.php is loaded early by some other plugin on a multisite installation, it leads to an error because AUTH_COOKIE is undefined and WP doesn't check for it first
			return 0;
		}
		$id = get_current_user_id();
		return $id ? $id : 0;
	}
	public function logLeechAndBlock($type) { //404 or hit
		if (!wfRateLimit::mightRateLimit($type)) {
			return;
		}
		
		wfRateLimit::countHit($type, wfUtils::getIP());
		
		if (wfRateLimit::globalRateLimit()->shouldEnforce($type)) {
			$this->takeBlockingAction('maxGlobalRequests', wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_THROTTLEGLOBAL));
		}
		else if (wfRateLimit::crawlerViewsRateLimit()->shouldEnforce($type)) {
			$this->takeBlockingAction('maxRequestsCrawlers', wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_THROTTLECRAWLER)); //may not exit
		}
		else if (wfRateLimit::crawler404sRateLimit()->shouldEnforce($type)) {
			$this->takeBlockingAction('max404Crawlers', wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND));
		}
		else if (wfRateLimit::humanViewsRateLimit()->shouldEnforce($type)) {
			$this->takeBlockingAction('maxRequestsHumans', wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_THROTTLEHUMAN));
		}
		else if (wfRateLimit::human404sRateLimit()->shouldEnforce($type)) {
			$this->takeBlockingAction('max404Humans', wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND));
		}
	}
	
	public function tagRequestForBlock($reason, $wfsn = false) {
		if ($this->currentRequest !== null) {
			$this->currentRequest->statusCode = 403;
			$this->currentRequest->action = 'blocked:' . ($wfsn ? 'wfsn' : 'wordfence');
			$this->currentRequest->actionDescription = $reason;
		}
	}
	
	public function tagRequestForLockout($reason) {
		if ($this->currentRequest !== null) {
			$this->currentRequest->statusCode = 503;
			$this->currentRequest->action = 'lockedOut';
			$this->currentRequest->actionDescription = $reason;
		}
	}

	/**
	 * @return bool|int
	 */
	public function logHit() {
		$liveTrafficEnabled = wfConfig::liveTrafficEnabled();
		$action = $this->currentRequest->action;
		$logHitOK = $this->logHitOK();
		if (!$logHitOK) {
			return false;
		}
		if (!$liveTrafficEnabled && !$action) {
			return false;
		}
		if ($this->currentRequest !== null) {
			if ($this->currentRequest->save()) {
				return $this->currentRequest->getPrimaryKey();
			}
		}
		return false;
	}
	
	public function getHits($hitType /* 'hits' or 'logins' */, $type, $afterTime, $limit = 50, $IP = false){
		global $wpdb;
		$IPSQL = "";
		$sqlArgs = array($afterTime, $limit);
		if ($IP) {
			$ipHex = wfDB::binaryValueToSQLHex(wfUtils::inet_pton($IP));
			$IPSQL = " and IP={$ipHex} ";
		}
		
		if ($hitType == 'hits') {
			$securityOnly = !wfConfig::liveTrafficEnabled();
			$delayedHumanBotFiltering = false;
			
			$typeSQL = " ";
			if ($type == 'crawler') {
				if ($securityOnly) {
					$delayedHumanBotFiltering = true;
				}
				else {
					$now = time();
					$typeSQL = " and jsRun = 0 and {$now} - ctime > 30 ";
				}
			}
			else if ($type == 'gCrawler') {
				$typeSQL = " and isGoogle = 1 ";
			}
			else if ($type == '404') {
				$typeSQL = " and statusCode = 404 ";
			}
			else if ($type == 'human') {
				if ($securityOnly) {
					$delayedHumanBotFiltering = true;
				}
				else {
					$typeSQL = " and jsRun = 1 ";
				}
			}
			else if ($type == 'ruser') {
				$typeSQL = " and userID > 0 ";
			}
			else if ($type != 'hit') {
				wordfence::status(1, 'error', sprintf(/* translators: Error message. */ __("Invalid log type to wfLog: %s", 'wordfence'), $type));
				return false;
			}
			
			array_unshift($sqlArgs, "SELECT h.*, u.`display_name` FROM `{$this->hitsTable}` h
				LEFT JOIN `{$wpdb->users}` u ON h.`userID` = u.`ID`
				WHERE `ctime` > %f $IPSQL $typeSQL ORDER BY `ctime` DESC LIMIT %d");
			$results = call_user_func_array(array($this->getDB(), 'querySelect'), $sqlArgs);
			
			if ($delayedHumanBotFiltering) {
				$browscap = wfBrowscap::shared();
				foreach ($results as $index => $res) {
					if ($res['UA']) {
						$b = $browscap->getBrowser($res['UA']);
						if ($b && $b['Parent'] != 'DefaultProperties') {
							$jsRun = wfUtils::truthyToBoolean($res['jsRun']);
							if (!wfConfig::liveTrafficEnabled() && !$jsRun) {
								$jsRun = !(isset($b['Crawler']) && $b['Crawler']);
							}
							
							if ($type == 'crawler' && $jsRun || $type == 'human' && !$jsRun) {
								unset($results[$index]);
							}
						}
					}
				}
			}

		}
		else if ($hitType == 'logins') {
			array_unshift($sqlArgs, "select l.*, u.display_name from {$this->loginsTable} l
				LEFT JOIN {$wpdb->users} u on l.userID = u.ID
				where ctime > %f $IPSQL order by ctime desc limit %d");
			$results = call_user_func_array(array($this->getDB(), 'querySelect'), $sqlArgs ); 

		}
		else {
			wordfence::status(1, 'error', sprintf(/* translators: Error message. */ __("getHits got invalid hitType: %s", 'wordfence'), $hitType));
			return false;
		}
		$this->processGetHitsResults($type, $results);
		return $results;
	}

	private function processActionDescription($description) {
		switch ($description) {
		case wfWAFBlockI18n::WFWAF_BLOCK_UAREFIPRANGE:
			return __('UA/Hostname/Referrer/IP Range not allowed', 'wordfence');
		default:
			return $description;
		}
	}

	/**
	 * Processes the $results array into one suitable for output.
	 *
	 * Note: This may be passed content from different tables, so it won't always have the same columns present.
	 *
	 * @param string $type
	 * @param array $results
	 * @throws Exception
	 */
	public function processGetHitsResults($type, &$results) {
		$serverTime = $this->getDB()->querySingle("select unix_timestamp()");

		$this->resolveIPs($results);
		$ourURL = parse_url(site_url());
		$ourHost = strtolower($ourURL['host']);
		$ourHost = preg_replace('/^www\./i', '', $ourHost);
		$browscap = wfBrowscap::shared();

		$patternBlocks = wfBlock::patternBlocks(true);

		foreach ($results as &$res) {
			$res['id'] = (int) $res['id'];
			if (array_key_exists('statusCode', $res)) { $res['statusCode'] = (int) $res['statusCode']; }
			if (array_key_exists('userID', $res)) { $res['userID'] = (int) $res['userID']; }
			$res['ctime'] = floatval($res['ctime']);
			if (array_key_exists('attackLogTime', $res)) { $res['attackLogTime'] = floatval($res['attackLogTime']); }
			if (array_key_exists('jsRun', $res)) { $res['jsRun'] = wfUtils::truthyToBoolean($res['jsRun']); }
			if (array_key_exists('isGoogle', $res)) { $res['isGoogle'] = wfUtils::truthyToBoolean($res['isGoogle']); }
			if (array_key_exists('newVisit', $res)) { $res['newVisit'] = wfUtils::truthyToBoolean($res['newVisit']); }
			$res['type'] = $type;
			$res['IP'] = wfUtils::inet_ntop($res['IP']);
			$res['timeAgo'] = wfUtils::makeTimeAgo($serverTime - $res['ctime']);
			$res['blocked'] = false;
			$res['rangeBlocked'] = false;
			$res['ipRangeID'] = -1;
			if (array_key_exists('actionDescription', $res))
				$res['actionDescription'] = $this->processActionDescription($res['actionDescription']);
			
			$ipBlock = wfBlock::findIPBlock($res['IP']);
			if ($ipBlock !== false) {
				$res['blocked'] = true;
				$res['blockID'] = $ipBlock->id;
			}
			
			foreach ($patternBlocks as $b) {
				if (empty($b->ipRange)) { continue; }
				$range = new wfUserIPRange($b->ipRange);
				if ($range->isIPInRange($res['IP'])) {
					$res['rangeBlocked'] = true;
					$res['ipRangeID'] = $b->id;
					break;
				}
			}
			
			$res['extReferer'] = false;
			if (isset($res['referer'] ) && $res['referer']) {
				if(wfUtils::hasXSS($res['referer'] )){ //filtering out XSS
					$res['referer'] = '';
				}
			}
			if (isset( $res['referer'] ) && $res['referer']) {
				$refURL = parse_url($res['referer']);
				if (is_array($refURL) && isset($refURL['host']) && $refURL['host']) {
					$refHost = strtolower(preg_replace('/^www\./i', '', $refURL['host']));
					if ($refHost != $ourHost) {
						$res['extReferer'] = true;
						//now extract search terms
						$q = false;
						if (preg_match('/(?:google|bing|alltheweb|aol|ask)\./i', $refURL['host'])) {
							$q = 'q';
						}
						else if (stristr($refURL['host'], 'yahoo.')) {
							$q = 'p';
						}
						else if(stristr($refURL['host'], 'baidu.')) {
							$q = 'wd';
						}
						if ($q) {
							$queryVars = array();
							if (isset( $refURL['query'])) {
								parse_str($refURL['query'], $queryVars);
								if (isset($queryVars[$q])) {
									$res['searchTerms'] = urlencode($queryVars[$q]);
								}
							}
						}
					}
				}
				if ($res['extReferer']) {
					if (isset($referringPage) && stristr($referringPage['host'], 'google.'))
					{
						parse_str($referringPage['query'], $queryVars);
					}
				}
			}
			
			$res['browser'] = false;
			if ($res['UA']) {
				$b = $browscap->getBrowser($res['UA']);
				if ($b && $b['Parent'] != 'DefaultProperties') {
					$res['browser'] = array(
						'browser'   => !empty($b['Browser']) ? $b['Browser'] : "",
						'version'   => !empty($b['Version']) ? $b['Version'] : "",
						'platform'  => !empty($b['Platform']) ? $b['Platform'] : "",
						'isMobile'  => (isset($b['isMobileDevice']) && $b['isMobileDevice']),
						'isCrawler' => (isset($b['Crawler']) && $b['Crawler']),
					);
					
					if (isset($res['jsRun']) && !wfConfig::liveTrafficEnabled() && !wfUtils::truthyToBoolean($res['jsRun'])) {
						$res['jsRun'] = !(isset($b['Crawler']) && $b['Crawler']);
					}
				}
				else {
					$IP = wfUtils::getIP();
					$res['browser'] = array(
						'isCrawler' => !wfLog::isHumanRequest($IP, $res['UA'])
					);
				}
			}


			if ($res['userID']) {
				$ud = get_userdata($res['userID']);
				if ($ud) {
					$res['user'] = array(
						'editLink' => wfUtils::editUserLink($res['userID']),
						'display_name' => $res['display_name'],
						'ID' => (int) $res['userID']
					);
				}
			}
			else {
				$res['user'] = false;
			}
			
			if (array_key_exists('actionDescription', $res)) {
				$res['actionDescription'] = wfWAFBlockI18n::getTranslatedBlockDescription($res['actionDescription']);
			}
		}
	}

	public function resolveIPs(&$results){
		if(sizeof($results) < 1){ return; }
		$IPs = array();
		foreach($results as &$res){
			if($res['IP']){ //Can also be zero in case of non IP events
				$IPs[] = $res['IP'];
			}
		}
		$IPLocs = wfUtils::getIPsGeo($IPs); //Creates an array with IP as key and data as value

		foreach($results as &$res){
			$ip_printable = wfUtils::inet_ntop($res['IP']);
			if(isset($IPLocs[$ip_printable])){
				$res['loc'] = $IPLocs[$ip_printable];
			} else {
				$res['loc'] = false;
			}
		}
	}
	public function logHitOK(){
		if (!$this->canLogHit) {
			return false;
		}
		if (is_admin()) { return false; } //Don't log admin pageviews
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			if (preg_match('/WordPress\/' . $this->wp_version . '/i', $_SERVER['HTTP_USER_AGENT'])) { return false; } //Ignore regular requests generated by WP UA.
		}
		$userID = get_current_user_id();
		if (!$userID) {
			$userID = $this->effectiveUserID;
		}
		if ($userID) {
			$user = new WP_User($userID);
			if ($user && $user->exists()) {
				if (wfConfig::get('liveTraf_ignorePublishers') && ($user->has_cap('publish_posts') || $user->has_cap('publish_pages'))) {
					return false;
				}
				
				if (wfConfig::get('liveTraf_ignoreUsers')) {
					$ignored = explode(',', wfConfig::get('liveTraf_ignoreUsers'));
					foreach ($ignored as $entry) {
						if($user->user_login == $entry){
							return false;
						}
					}
				}
			}
		}
		if(wfConfig::get('liveTraf_ignoreIPs')){
			$IPs = explode(',', wfConfig::get('liveTraf_ignoreIPs'));
			$IP = wfUtils::getIP();
			foreach($IPs as $ignoreIP){
				if($ignoreIP == $IP){
					return false;
				}
			}
		}
		if( isset($_SERVER['HTTP_USER_AGENT']) && wfConfig::get('liveTraf_ignoreUA') ){
			if($_SERVER['HTTP_USER_AGENT'] == wfConfig::get('liveTraf_ignoreUA')){
				return false;
			}
		}

		return true;
	}
	private function getDB(){
		if(! $this->db){
			$this->db = new wfDB();
		}
		return $this->db;
	}
	public function firewallBadIPs() {
		$IP = wfUtils::getIP();
		if (wfBlock::isWhitelisted($IP)) {
			return;
		}

		//Range and UA pattern blocking
		$patternBlocks = wfBlock::patternBlocks(true);
		$userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$referrer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		foreach ($patternBlocks as $b) {
			if ($b->matchRequest($IP, $userAgent, $referrer) !== wfBlock::MATCH_NONE) {
				$b->recordBlock();
				wfActivityReport::logBlockedIP($IP, null, 'advanced');
				$this->currentRequest->actionDescription = wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_UAREFIPRANGE);
				$this->do503(3600, wfI18n::__("Advanced blocking in effect.", 'wordfence')); //exits
			}
		}

		// Country blocking
		$countryBlocks = wfBlock::countryBlocks(true);
		foreach ($countryBlocks as $b) {
			$match = $b->matchRequest($IP, false, false);
			if ($match === wfBlock::MATCH_COUNTRY_REDIR_BYPASS) {
				$bypassRedirDest = wfConfig::get('cbl_bypassRedirDest', '');
				
				$this->initLogRequest();
				$this->getCurrentRequest()->actionDescription = wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR);
				$this->getCurrentRequest()->statusCode = 302;
				$this->currentRequest->action = 'cbl:redirect';
				$this->logHit();
				
				wfUtils::doNotCache();
				wp_redirect($bypassRedirDest, 302);
				exit();
			}
			else if ($match === wfBlock::MATCH_COUNTRY_REDIR) {
				$b->recordBlock();
				wfConfig::inc('totalCountryBlocked');
				
				$this->initLogRequest();
				$this->getCurrentRequest()->actionDescription = wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY_REDIR, wfConfig::get('cbl_redirURL'));
				$this->getCurrentRequest()->statusCode = 503;
				if (!$this->getCurrentRequest()->action) {
					$this->currentRequest->action = 'blocked:wordfence';
				}
				$this->logHit();
				
				wfActivityReport::logBlockedIP($IP, null, 'country');
				
				wfUtils::doNotCache();
				wp_redirect(wfConfig::get('cbl_redirURL'), 302);
				exit();
			}
			else if ($match !== wfBlock::MATCH_NONE) {
				$b->recordBlock();
				$this->currentRequest->actionDescription = wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_COUNTRY);
				wfConfig::inc('totalCountryBlocked');
				wfActivityReport::logBlockedIP($IP, null, 'country');
				$this->do503(3600, wfI18n::__('Access from your area has been temporarily limited for security reasons', 'wordfence'));
			}
		}

		//Specific IP blocks
		$ipBlock = wfBlock::findIPBlock($IP);
		if ($ipBlock !== false) {
			$ipBlock->recordBlock();
			$secsToGo = max(0, $ipBlock->expiration - time());
			if (wfConfig::get('other_WFNet') && self::isAuthRequest()) { //It's an auth request and this IP has been blocked
				$this->getCurrentRequest()->action = 'blocked:wfsnrepeat';
				wordfence::wfsnReportBlockedAttempt($IP, 'login');
			}
			$reason = $ipBlock->reason;
			if ($ipBlock->type == wfBlock::TYPE_IP_MANUAL || $ipBlock->type == wfBlock::TYPE_IP_AUTOMATIC_PERMANENT) {
				$reason = wfWAFBlockI18n::getBlockDescription(wfWAFBlockI18n::WFWAF_BLOCK_MANUAL);
			}
			$this->do503($secsToGo, $reason); //exits
		}
	}

	private function takeBlockingAction($configVar, $reason) {
		if ($this->googleSafetyCheckOK()) {
			$action = wfConfig::get($configVar . '_action');
			if (!$action) {
				return;
			}
			
			$IP = wfUtils::getIP();
			$secsToGo = 0;
			if ($action == 'block') { //Rate limited - block temporarily
				$secsToGo = wfBlock::blockDuration();
				wfBlock::createRateBlock($reason, $IP, $secsToGo);
				wfActivityReport::logBlockedIP($IP, null, 'throttle');
				$this->tagRequestForBlock($reason);

				$alertCallback = array(new wfBlockAlert($IP, $reason, $secsToGo), 'send');

				do_action('wordfence_security_event', 'block', array(
					'ip' => $IP,
					'reason' => $reason,
					'duration' => $secsToGo,
				), $alertCallback);
				wordfence::status(2, 'info', sprintf(/* translators: 1. IP address. 2. Description of firewall action. */ __('Blocking IP %1$s. %2$s', 'wordfence'), $IP, $reason));
			}
			else if ($action == 'throttle') { //Rate limited - throttle
				$secsToGo = wfBlock::rateLimitThrottleDuration();
				wfBlock::createRateThrottle($reason, $IP, $secsToGo);
				wfActivityReport::logBlockedIP($IP, null, 'throttle');

				do_action('wordfence_security_event', 'throttle', array(
					'ip' => $IP,
					'reason' => $reason,
					'duration' => $secsToGo,
				));
				wordfence::status(2, 'info', sprintf(/* translators: 1. IP address. 2. Description of firewall action. */ __('Throttling IP %1$s. %2$s', 'wordfence'), $IP, $reason));
				wfConfig::inc('totalIPsThrottled');
			}
			$this->do503($secsToGo, $reason, false);
		}
		
		return;
	}
	
	/**
	 * Test if the current request is for wp-login.php or xmlrpc.php
	 *
	 * @return boolean
	 */
	private static function isAuthRequest() {
		if ((strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false)) {
			return true;
		}
		return false;
	}
	
	public function do503($secsToGo, $reason, $sendEventToCentral = true){
		$this->initLogRequest();

		if ($sendEventToCentral) {
			do_action('wordfence_security_event', 'block', array(
				'ip' => wfUtils::inet_ntop($this->currentRequest->IP),
				'reason' => $this->currentRequest->actionDescription ? $this->currentRequest->actionDescription : $reason,
				'duration' => $secsToGo,
			));
		}

		$this->currentRequest->statusCode = 503;
		if (!$this->currentRequest->action) {
			$this->currentRequest->action = 'blocked:wordfence';
		}
		if (!$this->currentRequest->actionDescription) {
			$this->currentRequest->actionDescription = "blocked: " . $reason;
		}
		
		$this->logHit();

		wfConfig::inc('total503s');
		wfUtils::doNotCache();
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		if($secsToGo){
			header('Retry-After: ' . $secsToGo);
		}
		$customText = wpautop(wp_strip_all_tags(wfConfig::get('blockCustomText', '')));
		$reason = wfWAFBlockI18n::getTranslatedBlockDescription($reason);
		require_once(dirname(__FILE__) . '/wf503.php');
		exit();
	}
	private function redirect($URL){
		wfUtils::doNotCache();
		wp_redirect($URL, 302);
		exit();
	}
	private function googleSafetyCheckOK(){ //returns true if OK to block. Returns false if we must not block.
		$cacheKey = md5( (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ' ' . wfUtils::getIP());
		//Cache so we can call this multiple times in one request
		if(! isset(self::$gbSafeCache[$cacheKey])){
			$nb = wfConfig::get('neverBlockBG');
			if($nb == 'treatAsOtherCrawlers'){
				self::$gbSafeCache[$cacheKey] = true; //OK to block because we're treating google like everyone else
			} else if($nb == 'neverBlockUA' || $nb == 'neverBlockVerified'){
				if(wfCrawl::isGoogleCrawler()){ //Check the UA using regex
					if($nb == 'neverBlockVerified'){
						if(wfCrawl::isVerifiedGoogleCrawler(wfUtils::getIP())){ //UA check passed, now verify using PTR if configured to
							self::$gbSafeCache[$cacheKey] = false; //This is a verified Google crawler, so no we can't block it
						} else {
							self::$gbSafeCache[$cacheKey] = true; //This is a crawler claiming to be Google but it did not verify
						}
					} else { //neverBlockUA
						self::$gbSafeCache[$cacheKey] = false; //User configured us to only do a UA check and this claims to be google so don't block
					}
				} else {
					self::$gbSafeCache[$cacheKey] = true; //This isn't a Google UA, so it's OK to block
				}
			} else {
				//error_log("Wordfence error: neverBlockBG option is not set.");
				self::$gbSafeCache[$cacheKey] = false; //Oops the config option is not set. This should never happen because it's set on install. So we return false to indicate it's not OK to block just for safety.
			}
		}
		if(! isset(self::$gbSafeCache[$cacheKey])){
			//error_log("Wordfence assertion fail in googleSafetyCheckOK: cached value is not set.");
			return false; //for safety
		}
		return self::$gbSafeCache[$cacheKey]; //return cached value
	}
	public function addStatus($level, $type, $msg){
		//$msg = '[' . sprintf('%.2f', memory_get_usage(true) / (1024 * 1024)) . '] ' . $msg;
		$this->getDB()->queryWrite("insert into " . $this->statusTable . " (ctime, level, type, msg) values (%s, %d, '%s', '%s')", sprintf('%.6f', microtime(true)), $level, $type, $msg);
	}
	public function getStatusEvents($lastID){
		if($lastID < 1){
			$lastID = $this->getDB()->querySingle("select id from " . $this->statusTable . " order by id desc limit 1000,1");
			if(! $lastID){
				$lastID = 0;
			}
		}
		$results = $this->getDB()->querySelect("select id, ctime, level, type, msg from " . $this->statusTable . " where id > %d order by id asc", $lastID);
		$timeOffset = 3600 * get_option('gmt_offset');
		foreach($results as &$rec){
			//$rec['timeAgo'] = wfUtils::makeTimeAgo(time() - $rec['ctime']);
			$rec['id'] = (int) $rec['id'];
			$rec['ctime'] = (float) $rec['ctime'];
			$rec['level'] = (int) $rec['level'];
			$rec['date'] = date('M d H:i:s', (int) $rec['ctime'] + $timeOffset);
			$rec['msg'] = wp_kses_data( (string) $rec['msg']);
		}
		return $results;
	}
	public function getSummaryEvents(){
		$results = $this->getDB()->querySelect("select ctime, level, type, msg from " . $this->statusTable . " where level = 10 order by ctime desc limit 100");
		$timeOffset = 3600 * get_option('gmt_offset');
		foreach($results as &$rec){
			$rec['date'] = date('M d H:i:s', (int) $rec['ctime'] + $timeOffset);
			if(strpos($rec['msg'], 'SUM_PREP:') === 0){
				break;
			}
		}
		return array_reverse($results);
	}

	/**
	 * @return string
	 */
	public function getGooglePattern() {
		return $this->googlePattern;
	}

}

/**
 *
 */
class wfUserIPRange {

	/**
	 * @var string|null
	 */
	private $ip_string;

	/**
	 * @param string|null $ip_string
	 */
	public function __construct($ip_string = null) {
		$this->setIPString($ip_string);
	}

	/**
	 * Check if the supplied IP address is within the user supplied range.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function isIPInRange($ip) {
		$ip_string = $this->getIPString();
		
		if (strpos($ip_string, '/') !== false) { //CIDR range -- 127.0.0.1/24
			return wfUtils::subnetContainsIP($ip_string, $ip);
		}
		else if (strpos($ip_string, '[') !== false) //Bracketed range -- 127.0.0.[1-100]
		{
			// IPv4 range
			if (strpos($ip_string, '.') !== false && strpos($ip, '.') !== false) {
				// IPv4-mapped-IPv6
				if (preg_match('/:ffff:([^:]+)$/i', $ip_string, $matches)) {
					$ip_string = $matches[1];
				}
				if (preg_match('/:ffff:([^:]+)$/i', $ip, $matches)) {
					$ip = $matches[1];
				}
				
				// Range check
				if (preg_match('/\[\d+\-\d+\]/', $ip_string)) {
					$IPparts = explode('.', $ip);
					$whiteParts = explode('.', $ip_string);
					$mismatch = false;
					if (count($whiteParts) != 4 || count($IPparts) != 4) {
						return false;
					}
					
					for ($i = 0; $i <= 3; $i++) {
						if (preg_match('/^\[(\d+)\-(\d+)\]$/', $whiteParts[$i], $m)) {
							if ($IPparts[$i] < $m[1] || $IPparts[$i] > $m[2]) {
								$mismatch = true;
							}
						}
						else if ($whiteParts[$i] != $IPparts[$i]) {
							$mismatch = true;
						}
					}
					if ($mismatch === false) {
						return true; // Is whitelisted because we did not get a mismatch
					}
				}
				else if ($ip_string == $ip) {
					return true;
				}
				
				// IPv6 range
			}
			else if (strpos($ip_string, ':') !== false && strpos($ip, ':') !== false) {
				$ip = strtolower(wfUtils::expandIPv6Address($ip));
				$ip_string = strtolower(self::expandIPv6Range($ip_string));
				if (preg_match('/\[[a-f0-9]+\-[a-f0-9]+\]/i', $ip_string)) {
					$IPparts = explode(':', $ip);
					$whiteParts = explode(':', $ip_string);
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
				else if ($ip_string == $ip) {
					return true;
				}
			}
		}
		else if (strpos($ip_string, '-') !== false) { //Linear range -- 127.0.0.1 - 127.0.1.100
			list($ip1, $ip2) = explode('-', $ip_string);
			$ip1N = wfUtils::inet_pton($ip1);
			$ip2N = wfUtils::inet_pton($ip2);
			$ipN = wfUtils::inet_pton($ip);
			return (strcmp($ip1N, $ipN) <= 0 && strcmp($ip2N, $ipN) >= 0);
		}
		else { //Treat as a literal IP
			$ip1 = wfUtils::inet_pton($ip_string);
			$ip2 = wfUtils::inet_pton($ip);
			if ($ip1 !== false && $ip1 == $ip2) {
				return true;
			}
		}
		
		return false;
	}

	private static function repeatString($string, $count) {
		if ($count <= 0)
			return '';
		return str_repeat($string, $count);
	}

	/**
	 * Expand a compressed printable range representation of an IPv6 address.
	 *
	 * @todo Hook up exceptions for better error handling.
	 * @todo Allow IPv4 mapped IPv6 addresses (::ffff:192.168.1.1).
	 * @param string $ip_range
	 * @return string
	 */
	public static function expandIPv6Range($ip_range) {
		$colon_count = substr_count($ip_range, ':');
		$dbl_colon_count = substr_count($ip_range, '::');
		if ($dbl_colon_count > 1) {
			return false;
		}
		$dbl_colon_pos = strpos($ip_range, '::');
		if ($dbl_colon_pos !== false) {
			$ip_range = str_replace('::', self::repeatString(':0000',
					(($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip_range) - 2) ? 9 : 8) - $colon_count) . ':', $ip_range);
			$ip_range = trim($ip_range, ':');
		}
		$colon_count = substr_count($ip_range, ':');
		if ($colon_count != 7) {
			return false;
		}

		$groups = explode(':', $ip_range);
		$expanded = '';
		foreach ($groups as $group) {
			if (preg_match('/\[([a-f0-9]{1,4})\-([a-f0-9]{1,4})\]/i', $group, $matches)) {
				$expanded .= sprintf('[%s-%s]', str_pad(strtolower($matches[1]), 4, '0', STR_PAD_LEFT), str_pad(strtolower($matches[2]), 4, '0', STR_PAD_LEFT)) . ':';
			} else if (preg_match('/[a-f0-9]{1,4}/i', $group)) {
				$expanded .= str_pad(strtolower($group), 4, '0', STR_PAD_LEFT) . ':';
			} else {
				return false;
			}
		}
		return trim($expanded, ':');
	}

	/**
	 * @return bool
	 */
	public function isValidRange() {
		return $this->isValidCIDRRange() || $this->isValidBracketedRange() || $this->isValidLinearRange() || wfUtils::isValidIP($this->getIPString());
	}
	
	public function isValidCIDRRange() { //e.g., 192.0.2.1/24
		$ip_string = $this->getIPString();
		if (preg_match('/[^0-9a-f:\/\.]/i', $ip_string)) { return false; }
		return wfUtils::isValidCIDRRange($ip_string);
	}
	
	public function isValidBracketedRange() { //e.g., 192.0.2.[1-10]
		$ip_string = $this->getIPString();
		if (preg_match('/[^0-9a-f:\.\[\]\-]/i', $ip_string)) { return false; }
		if (strpos($ip_string, '.') !== false) { //IPv4
			if (preg_match_all('/(\d+)/', $ip_string, $matches) > 0) {
				foreach ($matches[1] as $match) {
					$group = (int) $match;
					if ($group > 255 || $group < 0) {
						return false;
					}
				}
			}
			
			$group_regex = '([0-9]{1,3}|\[[0-9]{1,3}\-[0-9]{1,3}\])';
			return preg_match('/^' . str_repeat("{$group_regex}\\.", 3) . $group_regex . '$/i', $ip_string) > 0;
		}
		
		//IPv6
		if (strpos($ip_string, '::') !== false) {
			$ip_string = self::expandIPv6Range($ip_string);
		}
		if (!$ip_string) {
			return false;
		}
		$group_regex = '([a-f0-9]{1,4}|\[[a-f0-9]{1,4}\-[a-f0-9]{1,4}\])';
		return preg_match('/^' . str_repeat("$group_regex:", 7) . $group_regex . '$/i', $ip_string) > 0;
	}
	
	public function isValidLinearRange() { //e.g., 192.0.2.1-192.0.2.100
		$ip_string = $this->getIPString();
		if (preg_match('/[^0-9a-f:\.\-]/i', $ip_string)) { return false; }
		list($ip1, $ip2) = explode("-", $ip_string);
		
		if (!wfUtils::isValidIP($ip1) || !wfUtils::isValidIP($ip2)) {
			return false;
		}
		
		$ip1N = wfUtils::inet_pton($ip1);
		$ip2N = wfUtils::inet_pton($ip2);
		
		if ($ip1N === false || $ip2N === false) {
			return false;
		}
		
		return strcmp($ip1N, $ip2N) <= 0;
	}
	
	public function isMixedRange() { //e.g., 192.0.2.1-2001:db8::ffff
		$ip_string = $this->getIPString();
		if (preg_match('/[^0-9a-f:\.\-]/i', $ip_string)) { return false; }
		list($ip1, $ip2) = explode("-", $ip_string);
		
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

	protected function _sanitizeIPRange($ip_string) {
		if (!is_string($ip_string))
			return null;
		$ip_string = preg_replace('/\s/', '', $ip_string); //Strip whitespace
		$ip_string = preg_replace('/[\\x{2013}-\\x{2015}]/u', '-', $ip_string); //Non-hyphen dashes to hyphen
		$ip_string = strtolower($ip_string);
		
		if (preg_match('/^\d+-\d+$/', $ip_string)) { //v5 32 bit int style format
			list($start, $end) = explode('-', $ip_string);
			$start = long2ip($start);
			$end = long2ip($end);
			$ip_string = "{$start}-{$end}";
		}
		
		return $ip_string;
	}

	/**
	 * @return string|null
	 */
	public function getIPString() {
		return $this->ip_string;
	}

	/**
	 * @param string|null $ip_string
	 */
	public function setIPString($ip_string) {
		$this->ip_string = $this->_sanitizeIPRange($ip_string);
	}
}

/**
 * The function of this class is to detect admin users created via direct access to the database (in other words, not
 * through WordPress).
 */
class wfAdminUserMonitor {

	protected $currentAdminList = array();

	public function isEnabled() {
		$options = wfScanner::shared()->scanOptions();
		$enabled = $options['scansEnabled_suspiciousAdminUsers'];
		if ($enabled && is_multisite()) {
			if (!function_exists('wp_is_large_network')) {
				require_once(ABSPATH . WPINC . '/ms-functions.php');
			}
			$enabled = !wp_is_large_network('sites') && !wp_is_large_network('users');
		}
		return $enabled;
	}

	/**
	 *
	 */
	public function createInitialList() {
		$admins = $this->getCurrentAdmins();
		$adminUserList = array();
		foreach ($admins as $id => $user) {
			$adminUserList[$id] = 1;
		}
		wfConfig::set_ser('adminUserList', $adminUserList);
	}

	/**
	 * @param int $userID
	 */
	public function grantSuperAdmin($userID = null) {
		if ($userID) {
			$this->addAdmin($userID);
		}
	}

	/**
	 * @param int $userID
	 */
	public function revokeSuperAdmin($userID = null) {
		if ($userID) {
			$this->removeAdmin($userID);
		}
	}

	/**
	 * @param int $ID
	 * @param mixed $role
	 * @param mixed $old_roles
	 */
	public function updateToUserRole($ID = null, $role = null, $old_roles = null) {
		$admins = $this->getLoggedAdmins();
		if ($role !== 'administrator' && array_key_exists($ID, $admins)) {
			$this->removeAdmin($ID);
		} else if ($role === 'administrator') {
			$this->addAdmin($ID);
		}
	}

	/**
	 * @return array|bool
	 */
	public function checkNewAdmins() {
		$loggedAdmins = $this->getLoggedAdmins();
		$admins = $this->getCurrentAdmins();
		$suspiciousAdmins = array();
		foreach ($admins as $adminID => $v) {
			if (!array_key_exists($adminID, $loggedAdmins)) {
				$suspiciousAdmins[] = $adminID;
			}
		}
		return $suspiciousAdmins ? $suspiciousAdmins : false;
	}

	/**
	 * Checks if the supplied user ID is suspicious.
	 *
	 * @param int $userID
	 * @return bool
	 */
	public function isAdminUserLogged($userID) {
		$loggedAdmins = $this->getLoggedAdmins();
		return array_key_exists($userID, $loggedAdmins);
	}

	/**
	 * @param bool $forceReload
	 * @return array
	 */
	public function getCurrentAdmins($forceReload = false) {
		if (empty($this->currentAdminList) || $forceReload) {
			require_once(ABSPATH . WPINC . '/user.php');
			if (is_multisite()) {
				if (function_exists("get_sites")) {
					$sites = get_sites(array(
						'network_id' => null,
					));
				}
				else {
					$sites = wp_get_sites(array(
						'network_id' => null,
					));
				}
			} else {
				$sites = array(array(
					'blog_id' => get_current_blog_id(),
				));
			}

			// not very efficient, but the WordPress API doesn't provide a good way to do this.
			$this->currentAdminList = array();
			foreach ($sites as $siteRow) {
				$siteRowArray = (array) $siteRow;
				$user_query = new WP_User_Query(array(
					'blog_id' => $siteRowArray['blog_id'],
					'role'    => 'administrator',
				));
				$users = $user_query->get_results();
				if (is_array($users)) {
					/** @var WP_User $user */
					foreach ($users as $user) {
						$this->currentAdminList[$user->ID] = $user;
					}
				}
			}

			// Add any super admins that aren't also admins on a network
			$superAdmins = get_super_admins();
			foreach ($superAdmins as $userLogin) {
				$user = get_user_by('login', $userLogin);
				if ($user) {
					$this->currentAdminList[$user->ID] = $user;
				}
			}
		}

		return $this->currentAdminList;
	}

	public function getLoggedAdmins() {
		$loggedAdmins = wfConfig::get_ser('adminUserList', false);
		if (!is_array($loggedAdmins)) {
			$this->createInitialList();
			$loggedAdmins = wfConfig::get_ser('adminUserList', false);
		}
		if (!is_array($loggedAdmins)) {
			$loggedAdmins = array();
		}
		return $loggedAdmins;
	}

	/**
	 * @param int $userID
	 */
	public function addAdmin($userID) {
		$loggedAdmins = $this->getLoggedAdmins();
		if (!array_key_exists($userID, $loggedAdmins)) {
			$loggedAdmins[$userID] = 1;
			wfConfig::set_ser('adminUserList', $loggedAdmins);
		}
	}

	/**
	 * @param int $userID
	 */
	public function removeAdmin($userID) {
		$loggedAdmins = $this->getLoggedAdmins();
		if (array_key_exists($userID, $loggedAdmins) && !array_key_exists($userID, $this->getCurrentAdmins())) {
			unset($loggedAdmins[$userID]);
			wfConfig::set_ser('adminUserList', $loggedAdmins);
		}
	}
}

/**
 * Represents a request record
 * 
 * @property int $id
 * @property float $attackLogTime
 * @property float $ctime
 * @property string $IP
 * @property bool $jsRun
 * @property int $statusCode
 * @property bool $isGoogle
 * @property int $userID
 * @property string $URL
 * @property string $referer
 * @property string $UA
 * @property string $action
 * @property string $actionDescription
 * @property string $actionData
 */
class wfRequestModel extends wfModel {

	private static $actionDataEncodedParams = array(
		'paramKey',
		'paramValue',
		'path',
	);

	/**
	 * @param $actionData
	 * @return mixed|string|void
	 */
	public static function serializeActionData($actionData, $optionalKeys = array(), $maxLength = 65535) {
		if (is_array($actionData)) {
			foreach (self::$actionDataEncodedParams as $key) {
				if (array_key_exists($key, $actionData)) {
					$actionData[$key] = base64_encode($actionData[$key]);
				}
			}
		}
		do {
			$serialized = json_encode($actionData, JSON_UNESCAPED_SLASHES);
			$length = strlen($serialized);
			if ($length <= $maxLength)
				return $serialized;
			$excess = $length - $maxLength;
			$truncated = false;
			foreach ($optionalKeys as $key) {
				if (array_key_exists($key, $actionData)) {
					$fieldValue = $actionData[$key];
					$fieldLength = strlen($fieldValue);
					$truncatedLength = min($fieldLength, $excess);
					$truncated = true;
					if ($truncatedLength > 0) {
						$actionData[$key] = substr($fieldValue, 0, -$truncatedLength);
						$excess -= $truncatedLength;
					}
					else {
						unset($actionData[$key]);
						break;
					}
				}
			}
		} while ($truncated);
		return null;
	}

	/**
	 * @param $actionDataJSON
	 * @return mixed|string|void
	 */
	public static function unserializeActionData($actionDataJSON) {
		$actionData = json_decode($actionDataJSON, true);
		if (is_array($actionData)) {
			foreach (self::$actionDataEncodedParams as $key) {
				if (array_key_exists($key, $actionData)) {
					$actionData[$key] = base64_decode($actionData[$key]);
				}
			}
		}
		else {
			$actionData = array();
		}
		return $actionData;
	}

	private $columns = array(
		'id',
		'attackLogTime',
		'ctime',
		'IP',
		'jsRun',
		'statusCode',
		'isGoogle',
		'userID',
		'URL',
		'referer',
		'UA',
		'action',
		'actionDescription',
		'actionData',
	);

	public function getIDColumn() {
		return 'id';
	}

	public function getTable() {
		return wfDB::networkTable('wfHits');
	}

	public function hasColumn($column) {
		return in_array($column, $this->columns);
	}
	
	public function save() {
		$sapi = @php_sapi_name();
		if ($sapi == "cli") {
			return false;
		}
		
		return parent::save();
	}
}


class wfLiveTrafficQuery {

	protected $validParams = array(
		'id' => 'h.id',
		'ctime' => 'h.ctime',
		'ip' => 'h.ip',
		'jsrun' => 'h.jsrun',
		'statuscode' => 'h.statuscode',
		'isgoogle' => 'h.isgoogle',
		'userid' => 'h.userid',
		'url' => 'h.url',
		'referer' => 'h.referer',
		'ua' => 'h.ua',
		'action' => 'h.action',
		'actiondescription' => 'h.actiondescription',
		'actiondata' => 'h.actiondata',

		// wfLogins
		'user_login' => 'u.user_login',
		'username' => 'l.username',
	);

	/** @var wfLiveTrafficQueryFilterCollection */
	private $filters = array();

	/** @var wfLiveTrafficQueryGroupBy */
	private $groupBy;
	/**
	 * @var float|null
	 */
	private $startDate;
	/**
	 * @var float|null
	 */
	private $endDate;
	/**
	 * @var int
	 */
	private $limit;
	/**
	 * @var int
	 */
	private $offset;

	private $tableName;

	/** @var wfLog */
	private $wfLog;

	/**
	 * wfLiveTrafficQuery constructor.
	 *
	 * @param wfLog $wfLog
	 * @param wfLiveTrafficQueryFilterCollection $filters
	 * @param wfLiveTrafficQueryGroupBy $groupBy
	 * @param float $startDate
	 * @param float $endDate
	 * @param int $limit
	 * @param int $offset
	 */
	public function __construct($wfLog, $filters = null, $groupBy = null, $startDate = null, $endDate = null, $limit = 20, $offset = 0) {
		$this->wfLog = $wfLog;
		$this->filters = $filters;
		$this->groupBy = $groupBy;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
		$this->limit = $limit;
		$this->offset = $offset;
	}

	/**
	 * @return array|null|object
	 */
	public function execute() {
		global $wpdb;
		$delayedHumanBotFiltering = false;
		$humanOnly = false;
		$sql = $this->buildQuery($delayedHumanBotFiltering, $humanOnly);
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		if ($delayedHumanBotFiltering) {
			$browscap = wfBrowscap::shared();
			foreach ($results as $index => $res) {
				if ($res['UA']) {
					$b = $browscap->getBrowser($res['UA']);
					$jsRun = wfUtils::truthyToBoolean($res['jsRun']);
					if ($b && $b['Parent'] != 'DefaultProperties') {
						$jsRun = wfUtils::truthyToBoolean($res['jsRun']);
						if (!wfConfig::liveTrafficEnabled() && !$jsRun) {
							$jsRun = !(isset($b['Crawler']) && $b['Crawler']);
						}
					}
					
					if (!$humanOnly && $jsRun || $humanOnly && !$jsRun) {
						unset($results[$index]);
					}
				}
			}
		}
		
		$this->getWFLog()->processGetHitsResults('', $results);
		
		$verifyCrawlers = false;
		if ($this->filters !== null && count($this->filters->getFilters()) > 0) {
			$filters = $this->filters->getFilters();
			foreach ($filters as $f) {
				if (strtolower($f->getParam()) == "isgoogle") {
					$verifyCrawlers = true;
					break;
				}
			}
		}
		
		foreach ($results as $key => &$row) {
			if ($row['isGoogle'] && $verifyCrawlers) {
				if (!wfCrawl::isVerifiedGoogleCrawler($row['IP'], $row['UA'])) {
					unset($results[$key]); //foreach copies $results and iterates on the copy, so it is safe to mutate $results within the loop
					continue;
				}
			}

			$row['actionData'] = $row['actionData'] === null ? array() : (array) json_decode($row['actionData'], true);
		}
		return array_values($results);
	}

	/**
	 * @param mixed $delayedHumanBotFiltering Whether or not human/bot filtering should be applied in PHP rather than SQL.
	 * @param mixed $humanOnly When using delayed filtering, whether to show only humans or only bots.
	 * 
	 * @return string
	 * @throws wfLiveTrafficQueryException
	 */
	public function buildQuery(&$delayedHumanBotFiltering, &$humanOnly) {
		global $wpdb;
		$filters = $this->getFilters();
		$groupBy = $this->getGroupBy();
		$startDate = $this->getStartDate();
		$endDate = $this->getEndDate();
		$limit = absint($this->getLimit());
		$offset = absint($this->getOffset());

		$wheres = array("h.action != 'logged:waf'", "h.action != 'scan:detectproxy'");
		if ($startDate) {
			$wheres[] = $wpdb->prepare('h.ctime > %f', $startDate);
		}
		if ($endDate) {
			$wheres[] = $wpdb->prepare('h.ctime < %f', $endDate);
		}

		if ($filters instanceof wfLiveTrafficQueryFilterCollection) {
			if (!wfConfig::liveTrafficEnabled()) {
				$individualFilters = $filters->getFilters();
				foreach ($individualFilters as $index => $f) {
					if ($f->getParam() == 'jsRun' && $delayedHumanBotFiltering !== null && $humanOnly !== null) {
						$humanOnly = wfUtils::truthyToBoolean($f->getValue());
						if ($f->getOperator() == '!=') {
							$humanOnly = !$humanOnly;
						}
						$delayedHumanBotFiltering = true;
						unset($individualFilters[$index]);
					}
				}
				$filters->setFilters($individualFilters);
			}
			
			$filtersSQL = $filters->toSQL();
			if ($filtersSQL) {
				$wheres[] = $filtersSQL;
			}
		}

		$orderBy = 'ORDER BY h.ctime DESC';
		$select = ', l.username';
		$groupBySQL = '';
		if ($groupBy && $groupBy->validate()) {
			$groupBySQL = "GROUP BY {$groupBy->getParam()}";
			$orderBy = 'ORDER BY hitCount DESC';
			$select .= ', COUNT(h.id) as hitCount, MAX(h.ctime) AS lastHit, u.user_login AS username';
			
			if ($groupBy->getParam() == 'user_login') {
				$wheres[] = 'user_login IS NOT NULL';
			}
			else if ($groupBy->getParam() == 'action') {
				$wheres[] = '(statusCode = 403 OR statusCode = 503)';
			}
		}
		
		$where = join(' AND ', $wheres);
		if ($where) {
			$where = 'WHERE ' . $where;
		}
		if (!$limit || $limit > 1000) {
			$limit = 20;
		}
		$limitSQL = $wpdb->prepare('LIMIT %d, %d', $offset, $limit);
		
		$table_wfLogins = wfDB::networkTable('wfLogins');
		$sql = <<<SQL
SELECT h.*, u.display_name{$select} FROM {$this->getTableName()} h
LEFT JOIN {$wpdb->users} u on h.userID = u.ID
LEFT JOIN {$table_wfLogins} l on h.id = l.hitID
$where
$groupBySQL
$orderBy
$limitSQL
SQL;

		return $sql;
	}

	/**
	 * @param $param
	 * @return bool
	 */
	public function isValidParam($param) {
		return array_key_exists(strtolower($param), $this->validParams);
	}

	/**
	 * @param $getParam
	 * @return bool|string
	 */
	public function getColumnFromParam($getParam) {
		$getParam = strtolower($getParam);
		if (array_key_exists($getParam, $this->validParams)) {
			return $this->validParams[$getParam];
		}
		return false;
	}

	/**
	 * @return wfLiveTrafficQueryFilterCollection
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * @param wfLiveTrafficQueryFilterCollection $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}

	/**
	 * @return float|null
	 */
	public function getStartDate() {
		return $this->startDate;
	}

	/**
	 * @param float|null $startDate
	 */
	public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

	/**
	 * @return float|null
	 */
	public function getEndDate() {
		return $this->endDate;
	}

	/**
	 * @param float|null $endDate
	 */
	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}

	/**
	 * @return wfLiveTrafficQueryGroupBy
	 */
	public function getGroupBy() {
		return $this->groupBy;
	}

	/**
	 * @param wfLiveTrafficQueryGroupBy $groupBy
	 */
	public function setGroupBy($groupBy) {
		$this->groupBy = $groupBy;
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset) {
		$this->offset = $offset;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		if ($this->tableName === null) {
			$this->tableName = wfDB::networkTable('wfHits');
		}
		return $this->tableName;
	}

	/**
	 * @param string $tableName
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * @return wfLog
	 */
	public function getWFLog() {
		return $this->wfLog;
	}

	/**
	 * @param wfLog $wfLog
	 */
	public function setWFLog($wfLog) {
		$this->wfLog = $wfLog;
	}
}

class wfLiveTrafficQueryFilterCollection {

	private $filters = array();

	/**
	 * wfLiveTrafficQueryFilterCollection constructor.
	 *
	 * @param array $filters
	 */
	public function __construct($filters = array()) {
		$this->filters = $filters;
	}

	public function toSQL() {
		$params = array();
		$sql = '';
		$filters = $this->getFilters();
		if ($filters) {
			/** @var wfLiveTrafficQueryFilter $filter */
			foreach ($filters as $filter) {
				$params[$filter->getParam()][] = $filter;
			}
		}

		foreach ($params as $param => $filters) {
			// $sql .= '(';
			$filtersSQL = '';
			foreach ($filters as $filter) {
				$filterSQL = $filter->toSQL();
				if ($filterSQL) {
					$filtersSQL .= $filterSQL . ' OR ';
				}
			}
			if ($filtersSQL) {
				$sql .= '(' . substr($filtersSQL, 0, -4) . ') AND ';
			}
		}
		if ($sql) {
			$sql = substr($sql, 0, -5);
		}
		return $sql;
	}

	public function addFilter($filter) {
		$this->filters[] = $filter;
	}

	/**
	 * @return array
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * @param array $filters
	 */
	public function setFilters($filters) {
		$this->filters = $filters;
	}
}

class wfLiveTrafficQueryFilter {

	private $param;
	private $operator;
	private $value;

	protected $validOperators = array(
		'=',
		'!=',
		'contains',
		'!contains',
		'match',
		'!match',
		'hregexp',
		'hnotregexp',
	);

	/**
	 * @var wfLiveTrafficQuery
	 */
	private $query;

	/**
	 * wfLiveTrafficQueryFilter constructor.
	 *
	 * @param wfLiveTrafficQuery $query
	 * @param string $param
	 * @param string $operator
	 * @param string $value
	 */
	public function __construct($query, $param, $operator, $value) {
		$this->query = $query;
		$this->param = $param;
		$this->operator = $operator;
		$this->value = $value;
	}

	/**
	 * @return string|void
	 */
	public function toSQL() {
		$sql = '';
		if ($this->validate()) {
			/** @var wpdb $wpdb */
			global $wpdb;
			$operator = $this->getOperator();
			$param = $this->getQuery()->getColumnFromParam($this->getParam());
			if (!$param) {
				return $sql;
			}
			$value = $this->getValue();
			switch ($operator) {
				case 'contains':
					$like = addcslashes($value, '_%\\');
					$sql = $wpdb->prepare("$param LIKE %s", "%$like%");
					break;
					
				case '!contains':
					$like = addcslashes($value, '_%\\');
					$sql = $wpdb->prepare("$param NOT LIKE %s", "%$like%");
					break;

				case 'match':
					$sql = $wpdb->prepare("$param LIKE %s", $value);
					break;
				
				case '!match':
					$sql = $wpdb->prepare("$param NOT LIKE %s", $value);
					break;
				
				case 'hregexp':
					$sql = $wpdb->prepare("HEX($param) REGEXP %s", $value);
					break;
				
				case 'hnotregexp':
					$sql = $wpdb->prepare("HEX($param) NOT REGEXP %s", $value);
					break;

				default:
					$sql = $wpdb->prepare("$param $operator %s", $value);
					break;
			}
		}
		return $sql;
	}

	/**
	 * @return bool
	 */
	public function validate() {
		$valid = $this->isValidParam($this->getParam()) && $this->isValidOperator($this->getOperator());
		if (defined('WP_DEBUG') && WP_DEBUG) {
			if (!$valid) {
				throw new wfLiveTrafficQueryException("Invalid param/operator [{$this->getParam()}]/[{$this->getOperator()}] passed to " . get_class($this));
			}
			return true;
		}
		return $valid;
	}

	/**
	 * @param string $param
	 * @return bool
	 */
	public function isValidParam($param) {
		return $this->getQuery() && $this->getQuery()->isValidParam($param);
	}

	/**
	 * @param string $operator
	 * @return bool
	 */
	public function isValidOperator($operator) {
		return in_array($operator, $this->validOperators);
	}

	/**
	 * @return mixed
	 */
	public function getParam() {
		return $this->param;
	}

	/**
	 * @param mixed $param
	 */
	public function setParam($param) {
		$this->param = $param;
	}

	/**
	 * @return mixed
	 */
	public function getOperator() {
		return $this->operator;
	}

	/**
	 * @param mixed $operator
	 */
	public function setOperator($operator) {
		$this->operator = $operator;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * @return wfLiveTrafficQuery
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * @param wfLiveTrafficQuery $query
	 */
	public function setQuery($query) {
		$this->query = $query;
	}
}

class wfLiveTrafficQueryGroupBy {

	private $param;

	/**
	 * @var wfLiveTrafficQuery
	 */
	private $query;

	/**
	 * wfLiveTrafficQueryGroupBy constructor.
	 *
	 * @param wfLiveTrafficQuery $query
	 * @param string $param
	 */
	public function __construct($query, $param) {
		$this->query = $query;
		$this->param = $param;
	}

	/**
	 * @return bool
	 * @throws wfLiveTrafficQueryException
	 */
	public function validate() {
		$valid = $this->isValidParam($this->getParam());
		if (defined('WP_DEBUG') && WP_DEBUG) {
			if (!$valid) {
				throw new wfLiveTrafficQueryException("Invalid param [{$this->getParam()}] passed to " . get_class($this));
			}
			return true;
		}
		return $valid;
	}

	/**
	 * @param string $param
	 * @return bool
	 */
	public function isValidParam($param) {
		return $this->getQuery() && $this->getQuery()->isValidParam($param);
	}

	/**
	 * @return wfLiveTrafficQuery
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * @param wfLiveTrafficQuery $query
	 */
	public function setQuery($query) {
		$this->query = $query;
	}

	/**
	 * @return mixed
	 */
	public function getParam() {
		return $this->param;
	}

	/**
	 * @param mixed $param
	 */
	public function setParam($param) {
		$this->param = $param;
	}

}


class wfLiveTrafficQueryException extends Exception {

}

class wfErrorLogHandler {
	public static function getErrorLogs($deepSearch = false) {
		static $errorLogs = null;
		
		if ($errorLogs === null) {
			$searchPaths = array(ABSPATH, ABSPATH . 'wp-admin', ABSPATH . 'wp-content');
			
			$homePath = wfUtils::getHomePath();
			if (!in_array($homePath, $searchPaths)) {
				$searchPaths[] = $homePath;
			}
			
			$errorLogPath = ini_get('error_log');
			if (!empty($errorLogPath) && !in_array($errorLogPath, $searchPaths)) {
				$searchPaths[] = $errorLogPath;
			}
			
			$errorLogs = array();
			foreach ($searchPaths as $s) {
				$errorLogs = array_merge($errorLogs, self::_scanForLogs($s, $deepSearch));
			}
		}
		return $errorLogs;
	}
	
	private static function _scanForLogs($path, $deepSearch = false) {
		static $processedFolders = array(); //Protection for endless loops caused by symlinks
		if (is_file($path)) {
			$file = basename($path);
			if (preg_match('#(?:^php_errorlog$|error_log(\-\d+)?$|\.log$)#i', $file)) {
				return array($path => is_readable($path));
			}
			return array();
		}
		
		$path = untrailingslashit($path);
		if (empty($path)) {
			return array();
		}
		
		$contents = @scandir($path);
		if (!is_array($contents)) {
			return array();
		}
		
		$processedFolders[$path] = true;
		$errorLogs = array();
		foreach ($contents as $name) {
			if ($name == '.' || $name == '..') { continue; }
			$testPath = $path . DIRECTORY_SEPARATOR . $name;
			if (!array_key_exists($testPath, $processedFolders)) {
				if ((is_dir($testPath) && $deepSearch) || !is_dir($testPath)) {
					$errorLogs = array_merge($errorLogs, self::_scanForLogs($testPath, $deepSearch));
				}
			}
		}
		return $errorLogs;
	}
	
	public static function outputErrorLog($path) {
		$errorLogs = self::getErrorLogs();
		if (!isset($errorLogs[$path])) { //Only allow error logs we've identified
			global $wp_query;
			$wp_query->set_404();
			status_header(404);
			nocache_headers();
			
			$template = get_404_template();
			if ($template && file_exists($template)) {
				include($template);
			}
			exit;
		}
		
		$fh = @fopen($path, 'r');
		if (!$fh) {
			status_header(503);
			nocache_headers();
			echo "503 Service Unavailable";
			exit;
		}
		
		$headersOutputted = false;
		while (!feof($fh)) {
			$data = fread($fh, 1 * 1024 * 1024); //read 1 megs max per chunk
			if ($data === false) { //Handle the error where the file was reported readable but we can't actually read it
				status_header(503);
				nocache_headers();
				echo "503 Service Unavailable";
				exit;
			}
		
			if (!$headersOutputted) {
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename="' . basename($path));
				$headersOutputted = true;
			}
			echo $data;
		}
		exit;
	}
}
