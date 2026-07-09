<?php
if (defined('WFWAF_VERSION') && !defined('WFWAF_RUN_COMPLETE')) {

class wfWAF {

	const AUTH_COOKIE = 'wfwaf-authcookie';

	/**
	 * @var wfWAF
	 */
	private static $instance;
	protected $blacklistedParams;
	protected $whitelistedParams;
	protected $variables = array();

	/**
	 * @return wfWAF
	 */
	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * @param wfWAF $instance
	 */
	public static function setInstance($instance) {
		self::$instance = $instance;
	}
	
	/**
	 * @var wfWAFStorageInterface
	 */
	private static $sharedStorageEngine;
	private static $fallbackStorageEngine;
	
	/**
	 * @return wfWAFStorageInterface
	 */
	public static function getSharedStorageEngine() {
		return self::$sharedStorageEngine;
	}
	
	/**
	 * @param wfWAFStorageInterface $instance
	 */
	public static function setSharedStorageEngine($sharedStorageEngine, $fallback = false) {
		self::$sharedStorageEngine = $sharedStorageEngine;
		self::$fallbackStorageEngine = $fallback;
	}

	public static function hasFallbackStorageEngine() {
		return self::$fallbackStorageEngine;
	}

	protected $rulesFile;
	protected $trippedRules = array();
	protected $failedRules = array();
	protected $scores = array();
	protected $scoresXSS = array();
	protected $failScores = array();
	protected $rules = array();
	/**
	 * @var wfWAFRequestInterface
	 */
	private $request;
	/**
	 * @var wfWAFStorageInterface
	 */
	private $storageEngine;
	/**
	 * @var wfWAFEventBus
	 */
	private $eventBus;
	private $debug = array();
	private $disabledRules;
	private $publicKey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAzovUDp/qu7r6LT5d8dLL
H/87aRrCjUd6XtnG+afAPVfMKNp4u4L+UuYfw1RfpfquP/zLMGdfmJCUp/oJywkW
Rkqo+y7pDuqIFQ59dHvizmYQRvaZgvincBDpey5Ek9AFfB9fqYYnH9+eQw8eLdQi
h6Zsh8RsuxFM2BW6JD9Km7L5Lyxw9jU+lye7I3ICYtUOVxc3n3bJT2SiIwHK57pW
g/asJEUDiYQzsaa90YPOLdf1Ysz2rkgnCduQaEGz/RPhgUrmZfKwq8puEmkh7Yee
auEa+7b+FGTKs7dUo2BNGR7OVifK4GZ8w/ajS0TelhrSRi3BBQCGXLzUO/UURUAh
1QIDAQAB
-----END PUBLIC KEY-----';


	/**
	 * @param wfWAFRequestInterface $request
	 * @param wfWAFStorageInterface $storageEngine
	 * @param wfWAFEventBus $eventBus
	 * @param string|null $rulesFile
	 */
	public function __construct($request, $storageEngine, $eventBus = null) {
		$this->setRequest($request);
		$this->setStorageEngine($storageEngine);
		$this->setEventBus($eventBus ? $eventBus : new wfWAFEventBus);
	}
	
	public function isReadOnly() {
		$storage = $this->getStorageEngine();
		if ($storage instanceof wfWAFStorageFile) {
			return !wfWAFStorageFile::allowFileWriting();
		}
		
		return false;
	}

	public function getGlobal($global) {
		if (wfWAFUtils::strpos($global, '.') === false) {
			return null;
		}
		list($prefix, $_global) = explode('.', $global);
		switch ($prefix) {
			case 'request':
				$method = "get" . ucfirst($_global);
				if (method_exists('wfWAFRequestInterface', $method)) {
					return call_user_func(array(
						$this->getRequest(),
						$method,
					));
				}
				break;
			case 'server':
				$key = wfWAFUtils::strtoupper($_global);
				if (isset($_SERVER) && array_key_exists($key, $_SERVER)) {
					return $_SERVER[$key];
				}
				break;
		}
		return null;
	}
	
	public function repairCron() {
		if (!wfWAFStorageFile::allowFileWriting()) { return false; }
		
		$cron = $this->getStorageEngine()->getConfig('cron', null, 'livewaf');
		$changed = false;
		if (!is_array($cron)) {
			$cron = array();
		}
		
		if (!$this->_hasCronOfType($cron, 'wfWAFCronFetchRulesEvent')) {
			$cron[] = new wfWAFCronFetchRulesEvent(time() +
				(86400 * ($this->getStorageEngine()->getConfig('isPaid', null, 'synced') ? .5 : 7)));
			$changed = true;
		}
		else {
			foreach ($cron as $index => $c) {
				if ($c instanceof wfWAFCronFetchRulesEvent && $this->getStorageEngine()->getConfig('isPaid', null, 'synced')) {
					if ($c->getFireTime() > (time() + 43200)) {
						$cron[$index] = $c->reschedule();
						$changed = true;
						break;
					}
				}
			}
		}
		
		if (!$this->_hasCronOfType($cron, 'wfWAFCronFetchBlacklistPrefixesEvent')) {
			$cron[] = new wfWAFCronFetchBlacklistPrefixesEvent(time() + 7200);
			$changed = true;
		}

		if (!$this->_hasCronOfType($cron, 'wfWAFCronFetchCookieRedactionPatternsEvent')) {
			$cron[] = new wfWAFCronFetchCookieRedactionPatternsEvent();
			$changed = true;
		}
		
		if ($changed) {
			$this->getStorageEngine()->setConfig('cron', $cron, 'livewaf');
		}
	}
	
	protected function _hasCronOfType($crons, $type) {
		foreach ($crons as $c) {
			if ($c instanceof $type) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 *
	 */
	public function runCron() {
		if (!wfWAFStorageFile::allowFileWriting()) { return false; }
		
		$storage = $this->getStorageEngine();
		
		if ((
				$storage->getConfig('attackDataNextInterval', null, 'transient') === null ||
				$storage->getConfig('attackDataNextInterval', time() + 0xffff, 'transient') <= time()
			) &&
			$storage->hasPreviousAttackData(microtime(true) - (60 * 5))
		) {
			$this->sendAttackData();
		}
		$cron = $storage->getConfig('cron', null, 'livewaf');
		$run = array();
		$updated = false;
		if (is_array($cron)) {
			/** @var wfWAFCronEvent $event */
			foreach ($cron as $index => $event) {
				$event->setWaf($this);
				if ($event->isInPast()) {
					$run[$index] = $event;
					$newEvent = $event->reschedule();
					if ($newEvent && $newEvent instanceof wfWAFCronEvent && $newEvent !== $event) {
						$cron[$index] = $newEvent;
						$updated = true;
					}
					else {
						unset($cron[$index]);
					}
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
	public function run() {
		$this->loadRules();
		if ($this->isDisabled()) {
			$this->eventBus->wafDisabled();
			return;
		}
		$this->runMigrations();
		$request = $this->getRequest();
		if ($request->getBody('wfwaf-false-positive-verified') && $this->currentUserCanWhitelist() &&
			wfWAFUtils::hash_equals($request->getBody('wfwaf-false-positive-nonce'), $this->getAuthCookieValue('nonce', ''))
		) {
			$urlParams = wfWAFUtils::json_decode($request->getBody('wfwaf-false-positive-params'), true);
			if (is_array($urlParams) && $urlParams) {
				$whitelistCount = 0;
				foreach ($urlParams as $urlParam) {
					$path = isset($urlParam['path']) ? $urlParam['path'] : false;
					$paramKey = isset($urlParam['paramKey']) ? $urlParam['paramKey'] : false;
					$ruleID = isset($urlParam['ruleID']) ? $urlParam['ruleID'] : false;
					if ($path && $paramKey && $ruleID) {
						$this->whitelistRuleForParam($path, $paramKey, $ruleID, array(
							'timestamp'   => time(),
							'description' => wfWAFI18n::__('Allowlisted via false positive dialog'),
							'source'	  => 'false-positive',
							'ip'          => $request->getIP(),
						));
						$whitelistCount++;
					}
				}
				exit(sprintf(wfWAFI18n::__('Successfully allowlisted %d params.'), $whitelistCount));
			}
		}

		$ip = $this->getRequest()->getIP();
		if ($this->isIPBlocked($ip)) {
			$this->eventBus->prevBlocked($ip);
			$e = new wfWAFBlockException();
			$e->setRequest($this->getRequest());
			$e->setFailedRules(array('blocked'));
			$this->blockAction($e);
		}

		try {
			$this->eventBus->beforeRunRules();
			$this->runRules();
			$this->eventBus->afterRunRules();

		} catch (wfWAFAllowException $e) {
			// Do nothing
			$this->eventBus->allow($ip, $e);

		} catch (wfWAFBlockException $e) {
			$this->eventBus->block($ip, $e);
			$this->blockAction($e);

		} catch (wfWAFBlockXSSException $e) {
			$this->eventBus->blockXSS($ip, $e);
			$this->blockXSSAction($e);

		} catch (wfWAFBlockSQLiException $e) {
			$this->eventBus->blockSQLi($ip, $e);
			$this->blockAction($e);
			
		}

		$this->runCron();
		$this->repairCron();

		// Check if this is signed request and update ruleset.

		$ping = $this->getRequest()->getBody('ping256');
		$pingResponse = $this->getRequest()->getBody('ping_response');

		if ($ping && $pingResponse &&
			$this->verifyPing($ping) &&
			$this->verifySignedRequest($this->getRequest()->getBody('signature256'), $this->getStorageEngine()->getConfig('apiKey', null, 'synced'))
		) {
			// $this->updateRuleSet(base64_decode($this->getRequest()->body('ping')));
			$event = new wfWAFCronFetchRulesEvent(time() - 2);
			$event->setWaf($this);
			$event->fire();

			header('Content-type: text/plain');
			$pingResponse = preg_replace('/[a-zA-Z0-9]/', '', $this->getRequest()->getBody('ping_response'));
			exit('Success: ' . hash('sha256', $this->getStorageEngine()->getConfig('apiKey', null, 'synced') . $pingResponse));
		}
	}

	/**
	 *
	 */
	public function loadRules() {
		$storageEngine = $this->getStorageEngine();
		if ($storageEngine instanceof wfWAFStorageFile) {
			$logLevel = error_reporting();
			if (wfWAFUtils::isCli()) { //Done to suppress errors from WP-CLI when the WAF is run on environments that have a server level constant to use the MySQLi storage engine that is not in place when running from the CLI
				error_reporting(0); 
			}
			
			// Acquire lock on this file so we're not including it while it's being written in another process.
			$handle = fopen($storageEngine->getRulesFile(), 'r');
			$locked = $handle !== false && flock($handle, LOCK_SH);
			/** @noinspection PhpIncludeInspection */
			include $storageEngine->getRulesFile();
			if ($locked)
				flock($handle, LOCK_UN);
			if ($handle !== false)
				fclose($handle);
			
			if (wfWAFUtils::isCli()) {
				error_reporting($logLevel);
			}
		} else {
			$wafRules = $storageEngine->getRules();
			if (is_array($wafRules)) {
				if (array_key_exists('rules', $wafRules)) {
					/** @var wfWAFRule $rule */
					foreach ($wafRules['rules'] as $rule) {
						$rule->setWAF($this);
						$this->rules[intval($rule->getRuleID())] = $rule;
					}
				}

				$properties = array(
					'failScores',
					'variables',
					'whitelistedParams',
					'blacklistedParams',
				);
				foreach ($properties as $property) {
					if (array_key_exists($property, $wafRules)) {
						$this->{$property} = $wafRules[$property];
					}
				}
			}
		}
		if ( !defined( 'WFWAF_RULES_LOADED' ) ) {
			define( 'WFWAF_RULES_LOADED', true );
		}
	}

	private function handleRuleFailure($rule, $cause) {
		global $wf_waf_failure;
		error_log("An unexpected error occurred while processing WAF rule " . $rule->getRuleID() . ": {$cause}");
		$wf_waf_failure = [
			'rule_id' => $rule->getRuleID(),
			'throwable' => $cause
		];
	}

	/**
	 * @throws wfWAFAllowException|wfWAFBlockException|wfWAFBlockXSSException
	 */
	public function runRules() {
		global $wf_waf_failure;
		/**
		 * @var int $ruleID
		 * @var wfWAFRule $rule
		 */
		foreach ($this->getRules() as $ruleID => $rule) {
			if (!$this->isRuleDisabled($ruleID)) {
				try {
					$rule->evaluate();
				}
				catch (wfWAFRunException $e) {
					throw $e;
				}
				catch (Exception $e) { // In PHP 5, Throwable does not exist
					$this->handleRuleFailure($rule, $e);
				}
				catch (Throwable $t) {
					$this->handleRuleFailure($rule, $t);
				}
			}
		}

		$blockActions = array();
		foreach ($this->failedRules as $paramKey => $categories) {
			foreach ($categories as $category => $failedRules) {
				foreach ($failedRules as $failedRule) {
					/**
					 * @var wfWAFRule $rule
					 * @var wfWAFRuleComparisonFailure $failedComparison
					 */
					$rule = $failedRule['rule'];
					$failedComparison = $failedRule['failedComparison'];
					$action = $failedRule['action'];

					if ($action !== 'log') {
						$score = $rule->getScore();
						if ($failedComparison->hasMultiplier()) {
							$score *= $failedComparison->getMultiplier();
						}
						if (!isset($this->failScores[$category])) {
							$this->failScores[$category] = 100;
						}
						if (!isset($this->scores[$paramKey][$category])) {
							$this->scores[$paramKey][$category] = 0;
						}
						$this->scores[$paramKey][$category] += $score;
						if ($this->scores[$paramKey][$category] >= $this->failScores[$category]) {
							$blockActions[$category] = array(
								'paramKey'         => $paramKey,
								'score'            => $this->scores[$paramKey][$category],
								'action'           => $action,
								'rule'             => $rule,
								'failedComparison' => $failedComparison,
							);
						}
						if (defined('WFWAF_DEBUG') && WFWAF_DEBUG) {
							$this->debug[] = sprintf("%s tripped %s for %s->%s('%s'). Score %d/%d", $paramKey, $action,
								$category, $failedComparison->getAction(), $failedComparison->getExpected(),
								$this->scores[$paramKey][$category], $this->failScores[$category]);
						}
					}
				}
			}
		}

		uasort($blockActions, array($this, 'sortBlockActions'));
		foreach ($blockActions as $blockAction) {
			call_user_func(array($this, $blockAction['action']), $blockAction['rule'], $blockAction['failedComparison'], false);
		}
	}

	/**
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortBlockActions($a, $b) {
		if ($a['score'] == $b['score']) {
			return 0;
		}
		return ($a['score'] > $b['score']) ? -1 : 1;
	}

	protected function runMigrations() {
		if (!wfWAFStorageFile::allowFileWriting()) { return false; }
		
		$storageEngine = $this->getStorageEngine();
		$currentVersion = $storageEngine->getConfig('version');
		if (wfWAFUtils::isVersionBelow(WFWAF_VERSION, $currentVersion)) {
			if (!$currentVersion) {
				$cron = array(
					new wfWAFCronFetchRulesEvent(time() +
						(86400 * ($this->getStorageEngine()->getConfig('isPaid', null, 'synced') ? .5 : 7))),
					new wfWAFCronFetchBlacklistPrefixesEvent(time() + 7200),
				);
				$this->getStorageEngine()->setConfig('cron', $cron, 'livewaf');
			}

			// Any migrations to newer versions go here.
			
			if (wfWAFUtils::isVersionBelow('1.0.2', $currentVersion)) {
				$event = new wfWAFCronFetchRulesEvent(time() - 2);
				$event->setWaf($this);
				$event->fire();
			}
			
			if (wfWAFUtils::isVersionBelow('1.0.3', $currentVersion)) {
				$this->getStorageEngine()->purgeIPBlocks();
				
				$cron = (array) $this->getStorageEngine()->getConfig('cron', null, 'livewaf');
				if (is_array($cron)) {
					$cron[] = new wfWAFCronFetchBlacklistPrefixesEvent(time() + 7200);
				}
				$this->getStorageEngine()->setConfig('cron', $cron, 'livewaf');
				
				$event = new wfWAFCronFetchBlacklistPrefixesEvent(time() - 2);
				$event->setWaf($this);
				$event->fire();
			}
			
			if (wfWAFUtils::isVersionBelow('1.0.4', $currentVersion)) {
				$movedKeys = array(
					'whitelistedURLParams' => 'livewaf',
					'cron' => 'livewaf',
					'attackDataNextInterval' => 'transient',
					'rulesLastUpdated' => 'transient',
					'premiumCount' => 'transient',
					'filePatterns' => 'transient',
					'filePatternCommonStrings' => 'transient',
					'filePatternIndexes' => 'transient',
					'signaturesLastUpdated' => 'transient',
					'signaturePremiumCount' => 'transient',
					'createInitialRulesDelay' => 'transient',
					'watchedIPs' => 'transient',
					'blockedPrefixes' => 'transient',
					'blacklistAllowedCache' => 'transient',
					'apiKey' => 'synced',
					'isPaid' => 'synced',
					'siteURL' => 'synced',
					'homeURL' => 'synced',
					'whitelistedIPs' => 'synced',
					'howGetIPs' => 'synced',
					'howGetIPs_trusted_proxies' => 'synced',
					'howGetIPs_trusted_proxies_unified' => 'synced',
					'pluginABSPATH' => 'synced',
					'other_WFNet' => 'synced',
					'serverIPs' => 'synced',
					'blockCustomText' => 'synced',
					'timeoffset_wf' => 'synced',
					'advancedBlockingEnabled' => 'synced',
					'disableWAFIPBlocking' => 'synced',
					'patternBlocks' => 'synced',
					'countryBlocks' => 'synced',
					'otherBlocks' => 'synced',
					'lockouts' => 'synced',
				);
				foreach ($movedKeys as $key => $category) {
					$value = $this->getStorageEngine()->getConfig($key, null, '');
					$this->getStorageEngine()->setConfig($key, $value, $category);

					if ($this->getStorageEngine() instanceof wfWAFStorageMySQL &&
						$this->getStorageEngine()->getStorageTable($category) === $this->getStorageEngine()->getStorageTable('')
					) {
						continue;
					}

					$this->getStorageEngine()->unsetConfig($key, '');
				}
			}
			if (wfWAFUtils::isVersionBelow('1.0.5', $currentVersion)) {
				$cron = $this->getStorageEngine()->getConfig('cron', array(), 'livewaf');
				$cron[] = new wfWAFCronFetchCookieRedactionPatternsEvent();
				$this->getStorageEngine()->setConfig('cron', $cron, 'livewaf');
			}
			
			$this->getStorageEngine()->setConfig('version', WFWAF_VERSION);
		}
	}

	/**
	 * @param wfWAFRule $rule
	 */
	public function tripRule($rule) {
		$this->trippedRules[] = $rule;
		$action = $rule->getAction();
		$scores = $rule->getScore();
		$categories = $rule->getCategory();
		if (is_array($categories)) {
			for ($i = 0; $i < count($categories); $i++) {
				if (is_array($action) && !empty($action[$i])) {
					$a = $action[$i];
				} else {
					$a = $action;
				}
				if ($this->isAllowedAction($a)) {
					$r = clone $rule;
					$r->setScore($scores[$i]);
					$r->setCategory($categories[$i]);
					/** @var wfWAFRuleComparisonFailure $failed */
					foreach ($r->getComparisonGroup()->getFailedComparisons() as $failed) {
						call_user_func(array($this, $a), $r, $failed);
					}
				}
			}
		} else {
			if ($this->isAllowedAction($action)) {
				/** @var wfWAFRuleComparisonFailure $failed */
				foreach ($rule->getComparisonGroup()->getFailedComparisons() as $failed) {
					call_user_func(array($this, $action), $rule, $failed);
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isInLearningMode() {
		return $this->getStorageEngine()->isInLearningMode();
	}

	/**
	 * @return bool
	 */
	public function isDisabled() {
		return $this->getStorageEngine()->isDisabled() || !WFWAF_ENABLED;
	}

	public function hasOpenSSL() {
		return function_exists('openssl_verify');
	}
	
	public function verifyPing($ping, $algorithm = 'sha256') {
		$hash = hash($algorithm, $this->getStorageEngine()->getConfig('apiKey', null, 'synced'));
		return wfWAFUtils::hash_equals($ping, $hash);
	}

	/**
	 * @param string $signature
	 * @param string $data
	 * @return bool
	 */
	public function verifySignedRequest($signature, $data, $algorithm = OPENSSL_ALGO_SHA256) {
		if (!$this->hasOpenSSL()) {
			return false;
		}
		$valid = openssl_verify($data, $signature, $this->getPublicKey(), $algorithm);
		return $valid === 1;
	}

	/**
	 * @param string $hash
	 * @param string $data
	 * @return bool
	 */
	public function verifyHashedRequest($hash, $data) {
		if ($this->hasOpenSSL()) {
			return false;
		}
		return wfWAFUtils::hash_equals($hash,
			wfWAFUtils::hash_hmac('sha1', $data, $this->getStorageEngine()->getConfig('apiKey', null, 'synced')));
	}

	/**
	 * @return array
	 */
	public function getMalwareSignatures() {
		try {
			$encoded = $this->getStorageEngine()->getConfig('filePatterns', null, 'transient');
			if (empty($encoded)) {
				return array();
			}
			
			$authKey = $this->getStorageEngine()->getConfig('authKey');
			$encoded = base64_decode($encoded);
			$paddedKey = wfWAFUtils::substr(str_repeat($authKey, ceil(strlen($encoded) / strlen($authKey))), 0, strlen($encoded));
			$json = $encoded ^ $paddedKey;
			$signatures = wfWAFUtils::json_decode($json, true);
			if (!is_array($signatures)) {
				return array();
			}
			return $signatures;
		}
		catch (Exception $e) {
			//Ignore
		}
		return array();
	}
	
	/**
	 * @param array $signatures
	 * @param bool $updateLastUpdatedTimestamp
	 */
	public function setMalwareSignatures($signatures, $updateLastUpdatedTimestamp = true) {
		try {
			if (!is_array($signatures)) {
				$signatures = array();
			}
			
			$authKey = $this->getStorageEngine()->getConfig('authKey');
			if (strlen($authKey) === 0) {
				return;
			}

			$json = wfWAFUtils::json_encode($signatures);
			$paddedKey = wfWAFUtils::substr(str_repeat($authKey, ceil(strlen($json) / strlen($authKey))), 0, strlen($json));
			$payload = $json ^ $paddedKey;
			$this->getStorageEngine()->setConfig('filePatterns', base64_encode($payload), 'transient');
			
			if ($updateLastUpdatedTimestamp) {
				$this->getStorageEngine()->setConfig('signaturesLastUpdated', is_int($updateLastUpdatedTimestamp) ? $updateLastUpdatedTimestamp : time(), 'transient');
			}
		}
		catch (Exception $e) {
			//Ignore
		}
	}
	
	/**
	 * @return array
	 */
	public function getMalwareSignatureCommonStrings() {
		try {
			$encoded = $this->getStorageEngine()->getConfig('filePatternCommonStrings', null, 'transient');
			if (empty($encoded)) {
				return array();
			}
			
			//Grab the list of words
			$authKey = $this->getStorageEngine()->getConfig('authKey');
			if (strlen($authKey) === 0) {
				return array();
			}

			$encoded = base64_decode($encoded);
			$paddedKey = wfWAFUtils::substr(str_repeat($authKey, ceil(strlen($encoded) / strlen($authKey))), 0, strlen($encoded));
			$json = $encoded ^ $paddedKey;
			$commonStrings = wfWAFUtils::json_decode($json, true);
			if (!is_array($commonStrings)) {
				return array();
			}
			
			//Grab the list of indexes
			$json = $this->getStorageEngine()->getConfig('filePatternIndexes', null, 'transient');
			if (empty($json)) {
				return array();
			}
			$signatureIndexes = wfWAFUtils::json_decode($json, true);
			if (!is_array($signatureIndexes)) {
				return array();
			}
			
			//Reconcile the list of indexes and transform into a list of words
			$signatureCommonWords = array();
			foreach ($signatureIndexes as $indexSet) {
				$entry = array();
				foreach ($indexSet as $i) {
					if (isset($commonStrings[$i])) {
						$entry[] = &$commonStrings[$i];
					}
				}
				$signatureCommonWords[] = $entry;
			}
			
			return $signatureCommonWords;
		}
		catch (Exception $e) {
			//Ignore
		}
		return array();
	}
	
	/**
	 * @param array $commonStrings
	 * @param array $signatureIndexes
	 */
	public function setMalwareSignatureCommonStrings($commonStrings, $signatureIndexes) {
		try {
			if (!is_array($commonStrings)) {
				$commonStrings = array();
			}
			
			if (!is_array($signatureIndexes)) {
				$signatureIndexes = array();
			}
			
			$authKey = $this->getStorageEngine()->getConfig('authKey');
			if (strlen($authKey) === 0) {
				return;
			}

			$json = wfWAFUtils::json_encode($commonStrings);
			$paddedKey = wfWAFUtils::substr(str_repeat($authKey, ceil(strlen($json) / strlen($authKey))), 0, strlen($json));
			$payload = $json ^ $paddedKey;
			$this->getStorageEngine()->setConfig('filePatternCommonStrings', base64_encode($payload), 'transient');
			
			$payload = wfWAFUtils::json_encode($signatureIndexes);
			$this->getStorageEngine()->setConfig('filePatternIndexes', $payload, 'transient');
		}
		catch (Exception $e) {
			//Ignore
		}
	}

	/**
	 * @param $rules
	 * @param bool|int $updateLastUpdatedTimestamp
	 * @throws wfWAFBuildRulesException
	 */
	public function updateRuleSet($rules, $updateLastUpdatedTimestamp = true) {
		try {
			if (is_string($rules)) {
				$ruleString = $rules;
				$parser = new wfWAFRuleParser(new wfWAFRuleLexer($rules), $this);
				$rules = $parser->parse();
			}

			$storageEngine = $this->getStorageEngine();
			if ($storageEngine instanceof wfWAFStorageFile) {
				if ((!is_file($storageEngine->getRulesFile()) && !is_writeable(dirname($storageEngine->getRulesFile()))) ||
					(is_file($storageEngine->getRulesFile()) && !is_writable($storageEngine->getRulesFile()))
				) {
					throw new wfWAFBuildRulesException('Rules file not writable.');
				}

				wfWAFStorageFile::atomicFilePutContents($storageEngine->getRulesFile(), sprintf(<<<PHP
<?php
if (!defined('WFWAF_VERSION') || defined('WFWAF_RULES_LOADED')) {
	exit('Access denied');
}
/*
	This file is generated automatically. Any changes made will be lost.
*/

%s?>
PHP
				, $this->buildRuleSet($rules)), 'rules');
				
				if (!empty($ruleString) && WFWAF_DEBUG && !file_exists($this->getStorageEngine()->getRulesDSLCacheFile())) {
					wfWAFStorageFile::atomicFilePutContents($this->getStorageEngine()->getRulesDSLCacheFile(), $ruleString, 'rules');
				}

			} else {
				$rules = $this->_preprocessRulesArray($rules);
				$this->getStorageEngine()->setRules($rules);
			}

			if ($updateLastUpdatedTimestamp) {
				$this->getStorageEngine()->setConfig('rulesLastUpdated', is_int($updateLastUpdatedTimestamp) ? $updateLastUpdatedTimestamp : time(), 'transient');
			}

		} catch (wfWAFBuildRulesException $e) {
			// Do something.
			throw $e;
		}
	}

	/**
	 * @param string|array $rules
	 * @return string
	 * @throws wfWAFException
	 */
	public function buildRuleSet($rules) {
		if (is_string($rules)) {
			$parser = new wfWAFRuleParser(new wfWAFRuleLexer($rules), $this);
			$rules = $parser->parse();
		}

		if (!array_key_exists('rules', $rules) || !is_array($rules['rules'])) {
			throw new wfWAFBuildRulesException('Invalid rule format passed to buildRuleSet.');
		}
		$exportedCode = '';
		
		$rules = $this->_preprocessRulesArray($rules);

		if (isset($rules['scores']) && is_array($rules['scores'])) {
			foreach ($rules['scores'] as $category => $score) {
				$exportedCode .= sprintf("\$this->failScores[%s] = %d;\n", var_export($category, true), $score);
			}
			$exportedCode .= "\n";
		}

		if (isset($rules['variables']) && is_array($rules['variables'])) {
			foreach ($rules['variables'] as $var => $value) {
				$exportedCode .= sprintf("\$this->variables[%s] = %s;\n", var_export($var, true),
					($value instanceof wfWAFRuleVariable) ? $value->render() : var_export($value, true));
			}
			$exportedCode .= "\n";
		}

		foreach (array('blacklistedParams', 'whitelistedParams') as $key) {
			if (isset($rules[$key]) && is_array($rules[$key])) {
				foreach ($rules[$key] as $paramKey => $payloads) {
					foreach ($payloads as $payload) { /** @var array|string $payload */
						if (is_string($payload)) {
							$exportedCode .= sprintf("\$this->{$key}[%s][] = %s;\n", 
								var_export($paramKey, true),
								var_export($payload, true)
							);
						}
						else if (array_key_exists('conditional', $payload)) {
							$exportedCode .= sprintf("\$this->{$key}[%s][] = array(\n%s => %s,\n%s => %s,\n%s => %s\n);\n", 
								var_export($paramKey, true),
								var_export('url', true), var_export($payload['url'], true),
								var_export('rules', true), var_export($payload['rules'], true),
								var_export('conditional', true), $payload['conditional']->render()
							);
						}
						else {
							$exportedCode .= sprintf("\$this->{$key}[%s][] = array(\n%s => %s,\n%s => %s,\n);\n", 
								var_export($paramKey, true),
								var_export('url', true), var_export($payload['url'], true),
								var_export('rules', true), var_export($payload['rules'], true)
							);
						}
					}
				}
				$exportedCode .= "\n";
			}
		}

		/** @var wfWAFRule $rule */
		foreach ($rules['rules'] as $rule) {
			$rule->setWAF($this);
			$exportedCode .= sprintf(<<<HTML
\$this->rules[%d] = %s;

HTML
				,
				$rule->getRuleID(),
				$rule->render()
			);
		}

		return $exportedCode;
	}
	
	/**
	 * Preprocesses the parsed rules array so it's suitable for use both by the file-based storage engine and the mysqli
	 * engine.
	 * 
	 * @param array $rules
	 * @return array
	 */
	protected function _preprocessRulesArray($rules) {
		$cleaned = $rules;
		foreach (array('blacklistedParams', 'whitelistedParams') as $key) {
			if (isset($rules[$key]) && is_array($rules[$key])) {
				/** @var wfWAFRuleParserURLParam $urlParam */
				foreach ($rules[$key] as $index => $urlParam) {
					unset($cleaned[$key][$index]);
					$paramKey = $urlParam->getParam();
					if (!array_key_exists($paramKey, $cleaned[$key])) {
						$cleaned[$key][$paramKey] = array();
					}
					
					if ($urlParam->getConditional()) {
						$cleaned[$key][$paramKey][] = array(
							'url' => $urlParam->getUrl(),
							'rules' => $urlParam->getRules(),
							'conditional' => $urlParam->getConditional(),
						);
					}
					else if ($urlParam->getRules()) {
						$cleaned[$key][$paramKey][] = array(
							'url' => $urlParam->getUrl(),
							'rules' => $urlParam->getRules(),
						);
					}
					else {
						$cleaned[$key][$paramKey][] = $urlParam->getUrl();
					}
				}
			}
		}
		return $cleaned;
	}

	/**
	 * @param $rules
	 * @return wfWAFRuleComparisonGroup
	 * @throws wfWAFBuildRulesException
	 */
	protected function _buildRuleSet($rules) {
		$ruleGroup = new wfWAFRuleComparisonGroup();
		foreach ($rules as $rule) {
			if (!array_key_exists('type', $rule)) {
				throw new wfWAFBuildRulesException('Invalid rule: type not set.');
			}
			switch ($rule['type']) {
				case 'comparison_group':
					if (!array_key_exists('comparisons', $rule) || !is_array($rule['comparisons'])) {
						throw new wfWAFBuildRulesException('Invalid rule format passed to _buildRuleSet.');
					}
					$ruleGroup->add($this->_buildRuleSet($rule['comparisons']));
					break;

				case 'comparison':
					if (array_key_exists('parameter', $rule)) {
						$rule['parameters'] = array($rule['parameter']);
					}

					foreach (array('action', 'expected', 'parameters') as $ruleRequirement) {
						if (!array_key_exists($ruleRequirement, $rule)) {
							throw new wfWAFBuildRulesException("Invalid rule: $ruleRequirement not set.");
						}
					}

					$ruleGroup->add(new wfWAFRuleComparison($this, $rule['action'], $rule['expected'], $rule['parameters']));
					break;

				case 'operator':
					if (!array_key_exists('operator', $rule)) {
						throw new wfWAFBuildRulesException('Invalid rule format passed to _buildRuleSet. operator not passed.');
					}
					$ruleGroup->add(new wfWAFRuleLogicalOperator($rule['operator']));
					break;

				default:
					throw new wfWAFBuildRulesException("Invalid rule type [{$rule['type']}] passed to _buildRuleSet.");
			}
		}
		return $ruleGroup;
	}

	public function isRuleDisabled($ruleID) {
		if ($this->disabledRules === null) {
			$this->disabledRules = $this->getStorageEngine()->getConfig('disabledRules');
			if (!is_array($this->disabledRules)) {
				$this->disabledRules = array();
			}
		}
		return !empty($this->disabledRules[$ruleID]);
	}
	
	public function getDisabledRuleIDs() {
		if ($this->disabledRules === null) {
			$this->disabledRules = $this->getStorageEngine()->getConfig('disabledRules');
			if (!is_array($this->disabledRules)) {
				$this->disabledRules = array();
			}
		}
		
		$ruleIDs = array();
		foreach ($this->disabledRules as $id => $value) {
			if (!empty($value)) {
				$ruleIDs[] = $id;
			}
		}
		return $ruleIDs;
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @throws wfWAFBlockException
	 */
	public function fail($rule, $failedComparison) {
		$category = $rule->getCategory();
		$paramKey = $failedComparison->getParamKey();
		$this->failedRules[$paramKey][$category][] = array(
			'rule'             => $rule,
			'failedComparison' => $failedComparison,
			'action'           => 'block',
		);
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @throws wfWAFBlockException
	 */
	public function failXSS($rule, $failedComparison) {
		$category = $rule->getCategory();
		$paramKey = $failedComparison->getParamKey();
		$this->failedRules[$paramKey][$category][] = array(
			'rule'             => $rule,
			'failedComparison' => $failedComparison,
			'action'           => 'blockXSS',
		);
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @throws wfWAFBlockException
	 */
	public function failSQLi($rule, $failedComparison) {
		$category = $rule->getCategory();
		$paramKey = $failedComparison->getParamKey();
		$this->failedRules[$paramKey][$category][] = array(
			'rule'             => $rule,
			'failedComparison' => $failedComparison,
			'action'           => 'blockSQLi',
		);
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @throws wfWAFAllowException
	 */
	public function allow($rule, $failedComparison) {
		// Exclude this request from further blocking
		$e = new wfWAFAllowException();
		$e->setFailedRules(array($rule));
		$e->setParamKey($failedComparison->getParamKey());
		$e->setParamValue($failedComparison->getParamValue());
		$e->setRequest($this->getRequest());
		throw $e;
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @param bool $updateFailedRules
	 * @throws wfWAFBlockException
	 */
	public function block($rule, $failedComparison, $updateFailedRules = true) {
		$paramKey = $failedComparison->getParamKey();
		$category = $rule->getCategory();

		if ($updateFailedRules) {
			$this->failedRules[$paramKey][$category][] = array(
				'rule'             => $rule,
				'failedComparison' => $failedComparison,
				'action'           => 'block',
			);
		}

		$e = new wfWAFBlockException();
		$e->setFailedRules(array($rule));
		$e->setParamKey($failedComparison->getParamKey());
		$e->setParamValue($failedComparison->getParamValue());
		$e->setRequest($this->getRequest());
		throw $e;
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @param bool $updateFailedRules
	 * @throws wfWAFBlockXSSException
	 */
	public function blockXSS($rule, $failedComparison, $updateFailedRules = true) {
		$paramKey = $failedComparison->getParamKey();
		$category = $rule->getCategory();

		if ($updateFailedRules) {
			$this->failedRules[$paramKey][$category][] = array(
				'rule'             => $rule,
				'failedComparison' => $failedComparison,
				'action'           => 'blockXSS',
			);
		}
		$e = new wfWAFBlockXSSException();
		$e->setFailedRules(array($rule));
		$e->setParamKey($failedComparison->getParamKey());
		$e->setParamValue($failedComparison->getParamValue());
		$e->setRequest($this->getRequest());
		throw $e;
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @param bool $updateFailedRules
	 * @throws wfWAFBlockSQLiException
	 */
	public function blockSQLi($rule, $failedComparison, $updateFailedRules = true) {
		// Verify the param looks like SQLi to help reduce false positives.
		if (!wfWAFSQLiParser::testForSQLi($failedComparison->getParamValue())) {
			return;
		}

		$paramKey = $failedComparison->getParamKey();
		$category = $rule->getCategory();

		if ($updateFailedRules) {
			$this->failedRules[$paramKey][$category][] = array(
				'rule'             => $rule,
				'failedComparison' => $failedComparison,
				'action'           => 'blockXSS',
			);
		}
		$e = new wfWAFBlockSQLiException();
		$e->setFailedRules(array($rule));
		$e->setParamKey($failedComparison->getParamKey());
		$e->setParamValue($failedComparison->getParamValue());
		$e->setRequest($this->getRequest());
		throw $e;
	}

	/**
	 * @param wfWAFRule $rule
	 * @param wfWAFRuleComparisonFailure $failedComparison
	 * @param bool $updateFailedRules
	 */
	public function log($rule, $failedComparison, $updateFailedRules = true) {
		$paramKey = $failedComparison->getParamKey();
		$category = $rule->getCategory();

		if ($updateFailedRules) {
			$this->failedRules[$paramKey][$category][] = array(
				'rule'             => $rule,
				'failedComparison' => $failedComparison,
				'action'           => 'log',
			);
		}

		$event=new wfWAFLogEvent(
			array($rule),
			$failedComparison->getParamKey(),
			$failedComparison->getParamValue(),
			$this->getRequest()
		);

		$this->recordLogEvent($event);
	}

	public function recordLogEvent($event) {
		$this->eventBus->log($this->getRequest()->getIP(), $event);
		$this->logAction($event);
	}

	/**
	 * @todo Hook up $httpCode
	 * @param wfWAFBlockException $e
	 * @param int $httpCode
	 */
	public function blockAction($e, $httpCode = 403, $redirect = false, $template = null) {
		$this->getStorageEngine()->logAttack($e->getFailedRules(), $e->getParamKey(), $e->getParamValue(), $e->getRequest(), $e->getRequest()->getMetadata());
		
		if ($redirect) {
			wfWAFUtils::redirect($redirect); // exits and emits no cache headers
		}
		
		if ($httpCode == 503) {
			wfWAFUtils::statusHeader(503);
			wfWAFUtils::doNotCache();
			if ($secsToGo = $e->getRequest()->getMetadata('503Time')) {
				header('Retry-After: ' . $secsToGo);
			}
			exit($this->getUnavailableMessage($e->getRequest()->getMetadata('503Reason'), $template));
		}
		
		header('HTTP/1.0 403 Forbidden');
		wfWAFUtils::doNotCache();
		exit($this->getBlockedMessage($template));
	}

	/**
	 * @todo Hook up $httpCode
	 * @param wfWAFBlockXSSException $e
	 * @param int $httpCode
	 */
	public function blockXSSAction($e, $httpCode = 403, $redirect = false) {
		$this->getStorageEngine()->logAttack($e->getFailedRules(), $e->getParamKey(), $e->getParamValue(), $e->getRequest(), $e->getRequest()->getMetadata());
		
		if ($redirect) {
			wfWAFUtils::redirect($redirect); // exits and emits no cache headers
		}
		
		if ($httpCode == 503) {
			wfWAFUtils::statusHeader(503);
			wfWAFUtils::doNotCache();
			if ($secsToGo = $e->getRequest()->getMetadata('503Time')) {
				header('Retry-After: ' . $secsToGo);
			}
			exit($this->getUnavailableMessage($e->getRequest()->getMetadata('503Reason')));
		}
		
		header('HTTP/1.0 403 Forbidden');
		wfWAFUtils::doNotCache();
		exit($this->getBlockedMessage());
	}
	
	public function logAction($event) {
		$failedRules = array_merge(array('logged'), $event->getFailedRules());
		$this->getStorageEngine()->logAttack($failedRules, $event->getParamKey(), $event->getParamValue(), $this->getRequest());
	}

	/**
	 * @return string
	 */
	public function getBlockedMessage($template = null) {
		if ($template === null) {
			if ($this->currentUserCanWhitelist()) {
				$template = '403-roadblock';
			}
			else {
				$template = '403';
			}
		}
		try {
			$homeURL = wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced');
			$siteURL = wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced');
			$customText = wfWAF::getInstance()->getStorageEngine()->getConfig('blockCustomText', null, 'synced');
			$errorNonce = '';
			if ($authCookie = wfWAF::getInstance()->parseAuthCookie()) {
				$errorNonce = wfWAF::getInstance()->getStorageEngine()->getConfig('errorNonce_' . (int) $authCookie['userID'], '', 'synced');
			}
		}
		catch (Exception $e) {
			//Do nothing
		}
		
		return wfWAFView::create($template, array(
			'waf' => $this,
			'homeURL' => $homeURL,
			'siteURL' => $siteURL,
			'customText' => $customText,
			'errorNonce' => $errorNonce,
		))->render();
	}
	
	/**
	 * @return string
	 */
	public function getUnavailableMessage($reason = '', $template = null) {
		if ($template === null) { $template = '503'; }
		try {
			$homeURL = wfWAF::getInstance()->getStorageEngine()->getConfig('homeURL', null, 'synced');
			$siteURL = wfWAF::getInstance()->getStorageEngine()->getConfig('siteURL', null, 'synced');
			$customText = wfWAF::getInstance()->getStorageEngine()->getConfig('blockCustomText', null, 'synced');
			$errorNonce = '';
			if ($authCookie = wfWAF::getInstance()->parseAuthCookie()) {
				$errorNonce = wfWAF::getInstance()->getStorageEngine()->getConfig('errorNonce_' . (int) $authCookie['userID'], '', 'synced');
			}
		}
		catch (Exception $e) {
			//Do nothing
		}
		
		return wfWAFView::create($template, array(
			'waf' => $this,
			'reason' => $reason,
			'homeURL' => $homeURL,
			'siteURL' => $siteURL,
			'customText' => $customText,
			'errorNonce' => $errorNonce,
		))->render();
	}

	/**
	 *
	 */
	public function whitelistFailedRules() {
		foreach ($this->failedRules as $paramKey => $categories) {
			foreach ($categories as $category => $failedRules) {
				foreach ($failedRules as $failedRule) {
					/**
					 * @var wfWAFRule $rule
					 * @var wfWAFRuleComparisonFailure $failedComparison
					 */
					$rule = $failedRule['rule'];
					if ($rule->getWhitelist()) {
						$failedComparison = $failedRule['failedComparison'];

						$data = array(
							'timestamp' => time(),
							'description' => 'Allowlisted while in Learning Mode.',
							'source' => 'learning-mode',
							'ip' => $this->getRequest()->getIP(),
						);
						if (function_exists('get_current_user_id')) {
							$data['userID'] = get_current_user_id();
						}
						$this->whitelistRuleForParam($this->getRequest()->getPath(), $failedComparison->getParamKey(),
							$rule->getRuleID(), $data);
					}
				}
			}
		}
	}

	/**
	 * @param string $path
	 * @param string $paramKey
	 * @param int $ruleID
	 * @param array $data
	 */
	public function whitelistRuleForParam($path, $paramKey, $ruleID, $data = array()) {
		if ($this->isParamKeyURLBlacklisted($ruleID, $paramKey, $path)) {
			return;
		}

		$whitelist = $this->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
		if (is_object($whitelist)) {
			$whitelist = (array) $whitelist;
		}
		else if (!is_array($whitelist)) {
			$whitelist = array();
		}
		
		if (is_array($ruleID)) {
			foreach ($ruleID as $id) {
				$whitelist[base64_encode($path) . "|" . base64_encode($paramKey)][$id] = $data;
			}
		} else {
			$whitelist[base64_encode($path) . "|" . base64_encode($paramKey)][$ruleID] = $data;
		}

		$this->getStorageEngine()->setConfig('whitelistedURLParams', $whitelist, 'livewaf');
	}

	/**
	 * @param int $ruleID
	 * @param string $urlPath
	 * @param string $paramKey
	 * @return bool
	 */
	public function isRuleParamWhitelisted($ruleID, $urlPath, $paramKey) {
		$urlPath = (string)$urlPath;
		$paramKey = (string)$paramKey;
		if ($this->isParamKeyURLBlacklisted($ruleID, $paramKey, $urlPath)) {
			return false;
		}

		if ($paramKey==='none' || (is_array($this->whitelistedParams) && array_key_exists($paramKey, $this->whitelistedParams)
			&& is_array($this->whitelistedParams[$paramKey]))
		) {
			foreach ($this->whitelistedParams[$paramKey] as $urlRegex) {
				if (is_array($urlRegex) || $urlRegex instanceof wfWAFRuleParserURLParam) {
					$rules = is_array($urlRegex) ? $urlRegex['rules'] : $urlRegex->getRules();
					if ($rules && !in_array($ruleID, $rules)) {
						continue;
					}
					
					$conditional = is_array($urlRegex) ? (isset($urlRegex['conditional']) ? $urlRegex['conditional'] : null) : $urlRegex->getConditional();
					if ($conditional && !$conditional->evaluate()) {
						continue;
					}
					
					$urlRegex = is_array($urlRegex) ? $urlRegex['url'] : $urlRegex->getUrl();
				}
				
				if (is_string($urlRegex) && preg_match($urlRegex, $urlPath)) {
					return true;
				}
			}
		}

		$whitelistKey = base64_encode($urlPath) . "|" . base64_encode($paramKey);
		$whitelist = $this->getStorageEngine()->getConfig('whitelistedURLParams', array(), 'livewaf');
		if (is_object($whitelist)) {
			$whitelist = (array) $whitelist;
		}
		else if (!is_array($whitelist)) {
			$whitelist = array();
		}

		if (array_key_exists($whitelistKey, $whitelist)) {
			foreach (array('all', $ruleID) as $key) {
				if (array_key_exists($key, $whitelist[$whitelistKey])) {
					$ruleData = $whitelist[$whitelistKey][$key];
					if (is_array($ruleData) && array_key_exists('disabled', $ruleData)) {
						return !$ruleData['disabled'];
					} else if ($ruleData) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 *
	 */
	public function sendAttackData() {
		if ($this->getStorageEngine()->getConfig('attackDataKey', false) === false) {
			$this->getStorageEngine()->setConfig('attackDataKey', mt_rand(0, 0xfff));
		}
		
		if (!$this->getStorageEngine()->getConfig('other_WFNet', true, 'synced')) {
			$this->getStorageEngine()->truncateAttackData();
			$this->getStorageEngine()->unsetConfig('attackDataNextInterval', 'transient');
			return;
		}

		$request = new wfWAFHTTP();
		try {
			$response = wfWAFHTTP::get(
				sprintf(WFWAF_API_URL_SEC . "waf-rules/%d.txt", $this->getStorageEngine()->getConfig('attackDataKey')),
				$request);

			if ($response instanceof wfWAFHTTPResponse) {
				if ($response->getBody() === 'ok') {
					$request = new wfWAFHTTP();
					$request->setHeaders(array(
						'Content-Type' => 'application/json',
					));
					$response = wfWAFHTTP::post(WFWAF_API_URL_SEC . "?" . http_build_query(array(
							'action' => 'send_waf_attack_data',
							'k'      => $this->getStorageEngine()->getConfig('apiKey', null, 'synced'),
							's'      => $this->getStorageEngine()->getConfig('siteURL', null, 'synced') ? $this->getStorageEngine()->getConfig('siteURL', null, 'synced') :
								sprintf('%s://%s/', $this->getRequest()->getProtocol(), rawurlencode($this->getRequest()->getHost())),
							'h'      => $this->getStorageEngine()->getConfig('homeURL', null, 'synced') ? $this->getStorageEngine()->getConfig('homeURL', null, 'synced') :
								sprintf('%s://%s/', $this->getRequest()->getProtocol(), rawurlencode($this->getRequest()->getHost())),
							't'		 => microtime(true),
							'lang'   => $this->getStorageEngine()->getConfig('WPLANG', null, 'synced'),
						), '', '&'), $this->getStorageEngine()->getAttackData(), $request);

					if ($response instanceof wfWAFHTTPResponse && $response->getBody()) {
						$jsonData = wfWAFUtils::json_decode($response->getBody(), true);
						if (is_array($jsonData) && array_key_exists('success', $jsonData)) {
							$this->getStorageEngine()->truncateAttackData();
							$this->getStorageEngine()->unsetConfig('attackDataNextInterval', 'transient');
						}
					}
				} else if (is_string($response->getBody()) && preg_match('/next check in: ([0-9]+)/', $response->getBody(), $matches)) {
					$this->getStorageEngine()->setConfig('attackDataNextInterval', time() + $matches[1], 'transient');
					if ($this->getStorageEngine()->isAttackDataFull()) {
						$this->getStorageEngine()->truncateAttackData();
					}
				}

				// Could be that the server is down, so hold off on sending data for a little while.
			} else {
				$this->getStorageEngine()->setConfig('attackDataNextInterval', time() + 7200, 'transient');
			}

		} catch (wfWAFHTTPTransportException $e) {
			error_log($e->getMessage());
		}
	}

	/**
	 * @param string $action
	 * @return array
	 */
	public function isAllowedAction($action) {
		static $actions;
		if (!isset($actions)) {
			$actions = array_flip($this->getAllowedActions());
		}
		return array_key_exists($action, $actions);
	}

	/**
	 * @return array
	 */
	public function getAllowedActions() {
		return array('fail', 'allow', 'block', 'failXSS', 'blockXSS', 'failSQLi', 'blockSQLi', 'log');
	}

	/**
	 *
	 */
	public function uninstall() {
		$this->getStorageEngine()->uninstall();
	}
	
	public function fileList() {
		$fileList = array();
		$rulesFile = $this->getCompiledRulesFile();
		if ($rulesFile !== null)
			array_push($fileList, $rulesFile);
		if (method_exists($this->getStorageEngine(), 'fileList')) {
			$fileList = array_merge($fileList, $this->getStorageEngine()->fileList());
		}
		return $fileList;
	}

	/**
	 * @param int $ruleID
	 * @param string $paramKey
	 * @param string $urlPath
	 * @return bool
	 */
	public function isParamKeyURLBlacklisted($ruleID, $paramKey, $urlPath) {
		if (is_array($this->blacklistedParams) && array_key_exists($paramKey, $this->blacklistedParams)
			&& is_array($this->blacklistedParams[$paramKey])
		) {
			foreach ($this->blacklistedParams[$paramKey] as $urlRegex) {
				if (is_array($urlRegex) || $urlRegex instanceof wfWAFRuleParserURLParam) {
					$rules = is_array($urlRegex) ? $urlRegex['rules'] : $urlRegex->getRules();
					if ($rules && !in_array($ruleID, $rules)) {
						continue;
					}
					
					$conditional = is_array($urlRegex) ? (isset($urlRegex['conditional']) ? $urlRegex['conditional'] : null) : $urlRegex->getConditional();
					if ($conditional && !$conditional->evaluate()) {
						continue;
					}
					
					$urlRegex = is_array($urlRegex) ? $urlRegex['url'] : $urlRegex->getUrl();
				}
				
				if (is_string($urlRegex) && preg_match($urlRegex, $urlPath)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function currentUserCanWhitelist() {
		if ($authCookie = $this->parseAuthCookie()) {
			return $authCookie['role'] === 'administrator';
		}
		return false;
	}

	/**
	 * @param string $capability
	 * @return bool
	 */
	public function checkCapability($capability) {
		if ($authCookie = $this->parseAuthCookie()) {
			return $authCookie['capabilities']!==null && in_array($capability, $authCookie['capabilities']);
		}
		return false;
	}

	/**
	 * @param string|null $cookieVal
	 * @return bool
	 */
	public function parseAuthCookie($cookieVal = null) {
		if ($cookieVal === null) {
			$cookieName = $this->getAuthCookieName();
			$cookieVal = !empty($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : '';
		}
		$pieces = explode('|', $cookieVal);
		$pieceCount = count($pieces);
		if ($pieceCount === 4) {
			list($userID, $role, $capabilityList, $signature) = $pieces;
			$capabilities = empty($capabilityList) ? array() : explode(',', $capabilityList);
		}
		else if ($pieceCount === 3) {
			list($userID, $role, $signature) = $pieces;
			$capabilities = null;
		}
		else {
			return false;
		}
		if (wfWAFUtils::hash_equals($signature, $this->getAuthCookieValue($userID, $role, $capabilities))) {
			return array(
				'userID' => $userID,
				'role'   => $role,
				'capabilities' => $capabilities
			);
		}
		return false;
	}

	/**
	 * @param int|string $userID
	 * @param string $role
	 * @param array $capabilities
	 * @return bool|string
	 */
	public function getAuthCookieValue($userID, $role, $capabilities = array()) {
		if (!is_array($capabilities))
			$capabilities = array();
		$algo = function_exists('hash') ? 'sha256' : 'sha1';
		return wfWAFUtils::hash_hmac($algo, $userID . $role . '|'. implode(',', $capabilities) . floor(time() / 43200), $this->getStorageEngine()->getConfig('authKey'));
	}
	
	/**
	 * @param string $action
	 * @return bool|string
	 */
	public function createNonce($action) {
		$userInfo = $this->parseAuthCookie();
		if ($userInfo === false) {
			$userInfo = array('userID' => 0, 'role' => ''); // Use an empty user like WordPress would
		}
		$userID = $userInfo['userID'];
		$role = $userInfo['role'];
		$algo = function_exists('hash') ? 'sha256' : 'sha1';
		return wfWAFUtils::hash_hmac($algo, $action . $userID . $role . floor(time() / 43200), $this->getStorageEngine()->getConfig('authKey'));
	}
	
	/**
	 * @param string $nonce
	 * @param string $action
	 * @return bool
	 */
	public function verifyNonce($nonce, $action) {
		if (empty($nonce)) {
			return false;
		}
		return wfWAFUtils::hash_equals($nonce, $this->createNonce($action));
	}

	/**
	 * @param string|null $host
	 * @return string
	 */
	public function getAuthCookieName($host = null) {
		if ($host === null) {
			$host = $this->getRequest()->getHost();
		}
		return self::AUTH_COOKIE . '-' . md5((string)$host);
	}

	/**
	 * @return string
	 */
	public function getCompiledRulesFile() {
		return $this->rulesFile;
	}

	/**
	 * @param string $rulesFile
	 */
	public function setCompiledRulesFile($rulesFile) {
		$this->rulesFile = $rulesFile;
	}

	/**
	 * @param $ip
	 * @return mixed
	 */
	public function isIPBlocked($ip) {
		return $this->getStorageEngine()->isIPBlocked($ip);
	}
	
	/**
	 * @param wfWAFRequest $request
	 * @return bool|array false if it should not be blocked, otherwise an array defining the context for the final action
	 */
	public function willPerformFinalAction($request) {
		return false;
	}

	/**
	 * @return array
	 */
	public function getTrippedRules() {
		return $this->trippedRules;
	}

	/**
	 * @return array
	 */
	public function getTrippedRuleIDs() {
		$ret = array();
		/** @var wfWAFRule $rule */
		foreach ($this->getTrippedRules() as $rule) {
			$ret[] = $rule->getRuleID();
		}
		return $ret;
	}

	public function showBench() {
		return sprintf("Bench: %f seconds\n\n", microtime(true) - $this->getRequest()->getTimestamp());
	}

	public function debug() {
		return join("\n", $this->debug) . "\n\n" . $this->showBench();
//		$debug = '';
//		/** @var wfWAFRule $rule */
//		foreach ($this->trippedRules as $rule) {
//			$debug .= $rule->debug();
//		}
//		return $debug;
	}

	/**
	 * @return array
	 */
	public function getScores() {
		return $this->scores;
	}

	/**
	 * @param string $var
	 * @return null
	 */
	public function getVariable($var) {
		if (array_key_exists($var, $this->variables)) {
			return $this->variables[$var];
		}
		return null;
	}

	/**
	 * @return wfWAFRequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param wfWAFRequestInterface $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}

	/**
	 * @return wfWAFStorageInterface
	 */
	public function getStorageEngine() {
		return $this->storageEngine;
	}

	/**
	 * @param wfWAFStorageInterface $storageEngine
	 */
	public function setStorageEngine($storageEngine) {
		$this->storageEngine = $storageEngine;
	}

	/**
	 * @return wfWAFEventBus
	 */
	public function getEventBus() {
		return $this->eventBus;
	}

	/**
	 * @param wfWAFEventBus $eventBus
	 */
	public function setEventBus($eventBus) {
		$this->eventBus = $eventBus;
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return $this->rules;
	}

	/**
	 * @param array $rules
	 */
	public function setRules($rules) {
		$this->rules = $rules;
	}

	/**
	 * @param int $ruleID
	 * @return null|wfWAFRule
	 */
	public function getRule($ruleID) {
		$rules = $this->getRules();
		if (is_array($rules) && array_key_exists($ruleID, $rules)) {
			return $rules[$ruleID];
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getPublicKey() {
		return $this->publicKey;
	}

	/**
	 * @param string $publicKey
	 */
	public function setPublicKey($publicKey) {
		$this->publicKey = $publicKey;
	}

	/**
	 * @return array
	 */
	public function getFailedRules() {
		return $this->failedRules;
	}

	public function getCookieRedactionPatterns($retry = true) {
		$patterns = $this->getStorageEngine()->getConfig('cookieRedactionPatterns', null, 'transient');
		if ($patterns === null) {
			if ($retry) {
				$event = new wfWAFCronFetchCookieRedactionPatternsEvent(time());
				$event->setWaf($this);
				$event->fire();
				return $this->getCookieRedactionPatterns(false);
			}
		}
		else {
			$patterns = wfWAFUtils::json_decode($patterns, true);
			if (is_array($patterns))
				return $patterns;
		}
		return null;
	}

	public function getVersion() {
		return WFWAF_VERSION;
	}

}

require_once __DIR__ . '/api.php';

/**
 * Serialized for use with the WAF cron.
 */
abstract class wfWAFCronEvent {

	abstract public function fire();

	abstract public function getNextFireTime();

	protected $fireTime;
	private $waf;

	/**
	 * @param int $fireTime
	 */
	public function __construct($fireTime = null) {
		$this->setFireTime($fireTime === null ? $this->getNextFireTime() : $fireTime);
	}

	/**
	 * @param int|null $time
	 * @return bool
	 */
	public function isInPast($time = null) {
		if ($time === null) {
			$time = time();
		}
		return $this->getFireTime() <= $time;
	}

	public function __sleep() {
		return array('fireTime');
	}

	/**
	 * @return mixed
	 */
	public function getFireTime() {
		return $this->fireTime;
	}

	/**
	 * @param mixed $fireTime
	 */
	public function setFireTime($fireTime) {
		$this->fireTime = $fireTime;
	}

	/**
	 * @return wfWAF
	 */
	public function getWaf() {
		return $this->waf;
	}

	/**
	 * @param wfWAF $waf
	 */
	public function setWaf($waf) {
		$this->waf = $waf;
	}

	public function reschedule() {
		$nextFireTime = $this->getNextFireTime();
		if ($nextFireTime === null)
			return false;
		$newEvent = new static($nextFireTime);
		return $newEvent;
	}

}

class wfWAFCronFetchRulesEvent extends wfWAFCronEvent {

	/**
	 * @var wfWAFHTTPResponse
	 */
	private $response;
	private $forceUpdate;

	public function __construct($fireTime, $forceUpdate = false) {
		parent::__construct($fireTime);
		$this->forceUpdate = $forceUpdate;
	}

	public function fire() {
		$waf = $this->getWaf();
		if (!$waf) {
			return false;
		}
		
		$success = true;
		$guessSiteURL = sprintf('%s://%s/', $waf->getRequest()->getProtocol(), $waf->getRequest()->getHost());
		try {
			$payload = array(
				'action' => 'get_waf_rules',
				'k' => $waf->getStorageEngine()->getConfig('apiKey', null, 'synced'),
				's' => $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') : $guessSiteURL,
				'h' => $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') : $guessSiteURL,
				'openssl' => $waf->hasOpenSSL() ? 1 : 0,
				'lang' => $waf->getStorageEngine()->getConfig('WPLANG', null, 'synced'),
				'waf_version' => $waf->getVersion()
			);
			$lastRuleHash=$this->forceUpdate ? null : $waf->getStorageEngine()->getConfig('lastRuleHash', null, 'transient');
			if($lastRuleHash!==null)
				$payload['hash']=$lastRuleHash;
			if ($waf->getStorageEngine()->getConfig('other_WFNet', true, 'synced')) {
				$payload['disabled'] = implode('|', $waf->getDisabledRuleIDs());
			}
			
			$this->response = wfWAFHTTP::get(WFWAF_API_URL_SEC . "?" . http_build_query($payload, '', '&'), null, 10, 5);
			if ($this->response) {
				if($this->response->getStatusCode() !== 304){
					$jsonData = wfWAFUtils::json_decode($this->response->getBody(), true);
					if (is_array($jsonData)) {

						if ($waf->hasOpenSSL() &&
							isset($jsonData['data']['signature256']) &&
							isset($jsonData['data']['rules']) &&
							$waf->verifySignedRequest(base64_decode($jsonData['data']['signature256']), $jsonData['data']['rules'])
						) {
							$waf->updateRuleSet(base64_decode($jsonData['data']['rules']),
								isset($jsonData['data']['timestamp']) ? $jsonData['data']['timestamp'] : true);
							$waf->getStorageEngine()->setConfig('lastRuleHash', $jsonData['data']['signature256'], 'transient');
							if (array_key_exists('premiumCount', $jsonData['data'])) {
								$waf->getStorageEngine()->setConfig('premiumCount', $jsonData['data']['premiumCount'], 'transient');
							}

						} else if (!$waf->hasOpenSSL() &&
							isset($jsonData['data']['hash']) &&
							isset($jsonData['data']['rules']) &&
							$waf->verifyHashedRequest($jsonData['data']['hash'], $jsonData['data']['rules'])
						) {
							$waf->updateRuleSet(base64_decode($jsonData['data']['rules']),
								isset($jsonData['data']['timestamp']) ? $jsonData['data']['timestamp'] : true);
							$waf->getStorageEngine()->setConfig('lastRuleHash', $jsonData['data']['hash'], 'transient');
							if (array_key_exists('premiumCount', $jsonData['data'])) {
								$waf->getStorageEngine()->setConfig('premiumCount', $jsonData['data']['premiumCount'], 'transient');
							}
						}
						else {
							$success = false;
						}
					}
					else {
						$success = false;
					}
				}
			}
			else {
				$success = false;
			}

			$lastMalwareSignatureUpdate=$waf->getStorageEngine()->getConfig('signaturesLastUpdated', 0, 'transient');
			$isPaid=$waf->getStorageEngine()->getConfig('isPaid', false, 'synced');
			//Only update malware signatures for free sites if they are older than 3 days plus an hour
			if ($isPaid || $this->forceUpdate || $lastMalwareSignatureUpdate < (time() - (259200 + 3600))) {
				$this->response = wfWAFHTTP::get(WFWAF_API_URL_SEC . "?" . http_build_query(array(
						'action'   => 'get_malware_signatures',
						'k'        => $waf->getStorageEngine()->getConfig('apiKey', null, 'synced'),
						's'        => $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') : $guessSiteURL,
						'h'        => $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') : $guessSiteURL,
						'openssl'  => $waf->hasOpenSSL() ? 1 : 0,
						'hash'	   => $this->forceUpdate ? null : $waf->getStorageEngine()->getConfig('lastMalwareHash', null, 'transient'),
						'cs-hash'  => $this->forceUpdate ? null : $waf->getStorageEngine()->getConfig('lastMalwareHashCommonStrings', null, 'transient'),
						'lang'   => $waf->getStorageEngine()->getConfig('WPLANG', null, 'synced')
					), '', '&'), null, 15, 5);
				if ($this->response) {
					if($this->response->getStatusCode() !== 304){
						$jsonData = wfWAFUtils::json_decode($this->response->getBody(), true);
						if (is_array($jsonData)) {
							if ($waf->hasOpenSSL() &&
								isset($jsonData['data']['signature256']) &&
								isset($jsonData['data']['signatures']) &&
								$waf->verifySignedRequest(base64_decode($jsonData['data']['signature256']), $jsonData['data']['signatures'])
							) {
								$waf->setMalwareSignatures(wfWAFUtils::json_decode(base64_decode($jsonData['data']['signatures'])),
									isset($jsonData['data']['timestamp']) ? $jsonData['data']['timestamp'] : true);
								$waf->getStorageEngine()->setConfig('lastMalwareHash', $jsonData['data']['signature256'], 'transient');
								if (array_key_exists('premiumCount', $jsonData['data'])) {
									$waf->getStorageEngine()->setConfig('signaturePremiumCount', $jsonData['data']['premiumCount'], 'transient');
								}

								if (array_key_exists('commonStringsSignature256', $jsonData['data']) && 
									array_key_exists('commonStrings', $jsonData['data']) && 
									array_key_exists('signatureIndexes', $jsonData['data']) &&
									$waf->verifySignedRequest(base64_decode($jsonData['data']['commonStringsSignature256']), $jsonData['data']['commonStrings'] . $jsonData['data']['signatureIndexes'])
								) {
									$waf->setMalwareSignatureCommonStrings(wfWAFUtils::json_decode(base64_decode($jsonData['data']['commonStrings'])), wfWAFUtils::json_decode(base64_decode($jsonData['data']['signatureIndexes'])));

									$waf->getStorageEngine()->setConfig('lastMalwareHashCommonStrings', $jsonData['data']['commonStringsSignature256'], 'transient');
								}

							} else if (!$waf->hasOpenSSL() &&
								isset($jsonData['data']['hash']) &&
								isset($jsonData['data']['signatures']) &&
								$waf->verifyHashedRequest($jsonData['data']['hash'], $jsonData['data']['signatures'])
							) {
								$waf->setMalwareSignatures(wfWAFUtils::json_decode(base64_decode($jsonData['data']['signatures'])),

									isset($jsonData['data']['timestamp']) ? $jsonData['data']['timestamp'] : true);
								$waf->getStorageEngine()->setConfig('lastMalwareHash', $jsonData['data']['hash'], 'transient');
								if (array_key_exists('premiumCount', $jsonData['data'])) {
									$waf->getStorageEngine()->setConfig('signaturePremiumCount', $jsonData['data']['premiumCount'], 'transient');
								}

								if (array_key_exists('commonStringsHash', $jsonData['data']) &&
									array_key_exists('commonStrings', $jsonData['data']) &&
									array_key_exists('signatureIndexes', $jsonData['data']) &&
									$waf->verifyHashedRequest($jsonData['data']['commonStringsHash'], $jsonData['data']['commonStrings'] . $jsonData['data']['signatureIndexes'])
								) {
									$waf->setMalwareSignatureCommonStrings(wfWAFUtils::json_decode(base64_decode($jsonData['data']['commonStrings'])), wfWAFUtils::json_decode(base64_decode($jsonData['data']['signatureIndexes'])));
									$waf->getStorageEngine()->setConfig('lastMalwareHashCommonStrings', $jsonData['data']['commonStringsHash'], 'transient');
								}
							}
							else {
								$success = false;
							}
						}
						else {
							$success = false;
						}
					}
				}
				else {
					$success = false;
				}
			}
		} catch (wfWAFHTTPTransportException $e) {
			error_log($e->getMessage());
			$success = false;
		} catch (wfWAFBuildRulesException $e) {
			error_log($e->getMessage());
			$success = false;
		}
		
		if ($success) {
			$waf->getStorageEngine()->setConfig('lastRuleUpdateCheck', time(), 'transient');
		}
		
		return $success;
	}

	public function getNextFireTime() {
		$waf = $this->getWaf();
		if (!$waf)
			return null;
		if ($this->response) {
			$headers = $this->response->getHeaders();
			if (isset($headers['Expires'])) {
				$timestamp = strtotime($headers['Expires']);
				// Make sure it's at least 2 hours ahead.
				if ($timestamp && $timestamp > (time() + 7200)) {
					return $timestamp;
				}
			}
		}
		return time() + (86400 * ($waf->getStorageEngine()->getConfig('isPaid', null, 'synced') ? .5 : 7));
	}

	public function getResponse() {
		return $this->response;
	}
}

class wfWAFCronFetchIPListEvent extends wfWAFCronEvent {
	
	public function fire() {
		//No op -- removed but class retained in case it still exists in serialized cron data
	}

	public function getNextFireTime() {
		return null;
	}

}

class wfWAFCronFetchBlacklistPrefixesEvent extends wfWAFCronEvent {
	
	public function fire() {
		$waf = $this->getWaf();
		if (!$waf) {
			return;
		}
		$guessSiteURL = sprintf('%s://%s/', $waf->getRequest()->getProtocol(), $waf->getRequest()->getHost());
		try {
			if ($waf->getStorageEngine()->getConfig('isPaid', null, 'synced')) {
				$request = new wfWAFHTTP();
				$response = wfWAFHTTP::get(WFWAF_API_URL_SEC . 'blacklist-prefixes.bin' . "?" . http_build_query(array(
						'k'      => $waf->getStorageEngine()->getConfig('apiKey', null, 'synced'),
						's'      => $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('siteURL', null, 'synced') : $guessSiteURL,
						'h'      => $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') ? $waf->getStorageEngine()->getConfig('homeURL', null, 'synced') : $guessSiteURL,
						't'		 => microtime(true),
						'lang'   => $waf->getStorageEngine()->getConfig('WPLANG', null, 'synced'),
					), '', '&'), $request);
				
				if ($response instanceof wfWAFHTTPResponse && $response->getBody()) {
					$waf->getStorageEngine()->setConfig('blockedPrefixes', base64_encode($response->getBody()), 'transient');
					$waf->getStorageEngine()->setConfig('blacklistAllowedCache', '', 'transient');
				}
			}
			
			$waf->getStorageEngine()->vacuum();
		} catch (wfWAFHTTPTransportException $e) {
			error_log($e->getMessage());
		}
	}

	public function getNextFireTime() {
		return time() + 7200;
	}

}

class wfWAFCronFetchCookieRedactionPatternsEvent extends wfWAFCronEvent {

	const INTERVAL = 604800;
	const RETRY_DELAY = 14400;

	public function fire() {
		$waf = $this->getWaf();
		if (!$waf)
			return;
		$storageEngine = $waf->getStorageEngine();
		$lastFailure = $storageEngine->getConfig('cookieRedactionLastUpdateFailure', null, 'transient');
		if ($lastFailure !== null && time() - (int) $lastFailure < self::RETRY_DELAY)
			return;
		try {
			$api = new wfWafApi($waf);
			$response = $api->actionGet('get_cookie_redaction_patterns');
			if ($response->getStatusCode() === 200) {
				$body = $response->getBody();
				$data = wfWAFUtils::json_decode($body, true);
				if (is_array($data) && array_key_exists('data', $data)) {
					$patterns = $data['data'];
					if (is_array($patterns)) {
						$storageEngine->setConfig('cookieRedactionPatterns', wfWAFUtils::json_encode($patterns), 'transient');
						return;
					}
				}
				error_log('Malformed cookie redaction patterns received, response body: ' . print_r($body, true));
			}
			else {
				error_log('Failed to retrieve cookie redaction patterns, response code: ' . $response->getStatusCode());
			}
		}
		catch (wfWafMissingApiKeyException $e) {
			// This is intentionally ignored as the API key may be missing during initial setup of the plugin
		}
		catch (wfWafApiException $e) {
			error_log('Failed to retrieve cookie redaction patterns: ' . $e->getMessage());
		}
		$storageEngine->setConfig('cookieRedactionLastUpdateFailure', time(), 'transient');
	}

	public function getNextFireTime() {
		return time() + self::INTERVAL;
	}

}
	
interface wfWAFObserver {
	
	public function prevBlocked($ip);
	
	public function block($ip, $exception);
	
	public function allow($ip, $exception);
	
	public function blockXSS($ip, $exception);
	
	public function blockSQLi($ip, $exception);
	
	public function log($ip, $event);
	
	public function wafDisabled();
	
	public function beforeRunRules();
	
	public function afterRunRules();
}

class wfWAFEventBus implements wfWAFObserver {

	private $observers = array();

	/**
	 * @param wfWAFObserver $observer
	 * @throws wfWAFEventBusException
	 */
	public function attach($observer) {
		if (!($observer instanceof wfWAFObserver)) {
			throw new wfWAFEventBusException('Observer supplied to wfWAFEventBus::attach must implement wfWAFObserver');
		}
		$this->observers[] = $observer;
	}

	/**
	 * @param wfWAFObserver $observer
	 */
	public function detach($observer) {
		$key = array_search($observer, $this->observers, true);
		if ($key !== false) {
			unset($this->observers[$key]);
		}
	}

	public function prevBlocked($ip) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->prevBlocked($ip);
		}
	}

	public function block($ip, $exception) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->block($ip, $exception);
		}
	}

	public function allow($ip, $exception) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->allow($ip, $exception);
		}
	}

	public function blockXSS($ip, $exception) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->blockXSS($ip, $exception);
		}
	}

	public function blockSQLi($ip, $exception) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->blockSQLi($ip, $exception);
		}
	}
	
	public function log($ip, $event) {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->log($ip, $event);
		}
	}


	public function wafDisabled() {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->wafDisabled();
		}
	}

	public function beforeRunRules() {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->beforeRunRules();
		}
	}

	public function afterRunRules() {
		/** @var wfWAFObserver $observer */
		foreach ($this->observers as $observer) {
			$observer->afterRunRules();
		}
	}
}

class wfWAFBaseObserver implements wfWAFObserver {

	public function prevBlocked($ip) {

	}

	public function block($ip, $exception) {

	}

	public function allow($ip, $exception) {

	}

	public function blockXSS($ip, $exception) {

	}

	public function blockSQLi($ip, $exception) {

	}
	
	public function log($ip, $exception) {
		
	}

	public function wafDisabled() {

	}

	public function beforeRunRules() {

	}

	public function afterRunRules() {

	}
}

class wfWAFException extends Exception {
}

class wfWAFRunException extends Exception {

	/** @var array */
	private $failedRules;
	/** @var string */
	private $paramKey;
	/** @var string */
	private $paramValue;
	/** @var wfWAFRequestInterface */
	private $request;

	/**
	 * @return array
	 */
	public function getFailedRules() {
		return $this->failedRules;
	}

	/**
	 * @param array $failedRules
	 */
	public function setFailedRules($failedRules) {
		$this->failedRules = $failedRules;
	}

	/**
	 * @return string
	 */
	public function getParamKey() {
		return $this->paramKey;
	}

	/**
	 * @param string $paramKey
	 */
	public function setParamKey($paramKey) {
		$this->paramKey = $paramKey;
	}

	/**
	 * @return string
	 */
	public function getParamValue() {
		return $this->paramValue;
	}

	/**
	 * @param string $paramValue
	 */
	public function setParamValue($paramValue) {
		$this->paramValue = $paramValue;
	}

	/**
	 * @return wfWAFRequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param wfWAFRequestInterface $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}
}

class wfWAFAllowException extends wfWAFRunException {
}

class wfWAFBlockException extends wfWAFRunException {
}

class wfWAFBlockXSSException extends wfWAFRunException {
}

class wfWAFBlockSQLiException extends wfWAFRunException {
}

class wfWAFBuildRulesException extends wfWAFException {
}

class wfWAFEventBusException extends wfWAFException {
}
}

class wfWAFLogEvent {

	private $failedRules;
	private $paramKey, $paramValue;
	private $request;

	public function __construct($failedRules=array(), $paramKey=null, $paramValue=null, $request=null){
		$this->failedRules=$failedRules;
		$this->paramKey=$paramKey;
		$this->paramValue=$paramValue;
		$this->request=$request;
	}

	public function getFailedRules(){
		return $this->failedRules;
	}

	public function getParamKey(){
		return $this->paramKey;
	}

	public function getParamValue(){
		return $this->paramValue;
	}

	public function getRequest(){
		return $this->request;
	}

}