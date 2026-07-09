<?php

/**
 *
 */
class wfWAFStorageMySQL implements wfWAFStorageInterface {

	private $_usingLowercase;

	/**
	 * @var wfWAFStorageEngineDatabase
	 */
	private $db;
	/**
	 * @var string
	 */
	private $tablePrefix;
	private $uninstalled;
	private $dataChanged = false;
	private $data = array();
	private $dataToSave = array();
	private $shutdownRegistry = null;
	
	/**
	 * @var array Represents the in-memory serialization status of a value in $data. When $serializedStatus contains a truthy value for a key, it means the value associated with that key in $data is deserialized. Only applicable to keys in `getSerializedParams()`
	 */
	private $serializedStatus = array();

	public $installing = false;

	/**
	 * @param wfWAFStorageEngineDatabase $engine
	 * @param string $tablePrefix
	 */
	public function __construct($engine, $tablePrefix = 'wp_', $shutdownRegistry = null) {
		$this->db = $engine;
		$this->tablePrefix = $tablePrefix;
		$this->shutdownRegistry = $shutdownRegistry === null ? wfShutdownRegistry::getDefaultInstance() : $shutdownRegistry;
	}

	public function usingLowercase() {
		if ($this->_usingLowercase === null) {
			$table = $this->tablePrefix . 'wfConfig';
			$tableExists = $this->getDb()->get_var("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND BINARY TABLE_NAME='$table'");
			$this->_usingLowercase = $tableExists !== $table;
		}
		return $this->_usingLowercase;
	}

	/**
	 * Returns the table with the site (single site installations) or network (multisite) prefix added.
	 *
	 * @param string $table
	 * @param bool $applyCaseConversion Whether or not to convert the table case to what is actually in use.
	 * @return string
	 */
	public function networkTable($table, $applyCaseConversion = true) {
		if ($this->usingLowercase() && $applyCaseConversion) {
			$table = strtolower($table);
		}
		return $this->tablePrefix . $table;
	}

	/**
	 * Check if there's attack before a certain timestamp.
	 *
	 * @param int $olderThan
	 * @return bool
	 */
	public function hasPreviousAttackData($olderThan) {
		$table = $this->networkTable('wfHits');
		$lastAttackDataTruncateTime = floatval($this->getConfig('lastAttackDataTruncateTime'));
		$count = $this->db->get_var('SELECT count(*) FROM ' . $table . ' where attackLogTime < ? and attackLogTime > ?', array(
			sprintf('%.6f', $olderThan),
			$lastAttackDataTruncateTime,
		));
		return $count > 0;
	}

	/**
	 * Check if there's attack data after a given timestamp.
	 *
	 * @param int $newerThan
	 * @return bool
	 */
	public function hasNewerAttackData($newerThan) {
		$table = $this->networkTable('wfHits');
		$lastAttackDataTruncateTime = floatval($this->getConfig('lastAttackDataTruncateTime'));
		$count = $this->db->get_var('SELECT count(*) FROM ' . $table . ' where attackLogTime > ?', array(
			sprintf('%.6f', max($newerThan, $lastAttackDataTruncateTime)),
		));
		return $count > 0;
	}

	/**
	 * Get all attack data.
	 *
	 *
	 */
	public function getAttackData() {
		$table = $this->networkTable('wfHits');
		$lastAttackDataTruncateTime = floatval($this->getConfig('lastAttackDataTruncateTime'));
		$results = $this->db->get_results('SELECT * FROM ' . $table . ' WHERE attackLogTime > ?', array(
			$lastAttackDataTruncateTime,
		));

		$data = array();
		foreach ($results as $row) {
			$actionData = wfWAFUtils::json_decode($row['actionData'], true);
			if (!is_array($actionData))
				$actionData = array();
			$data[] = array(
				$row['attackLogTime'],
				$row['ctime'],
				wfWAFUtils::inet_ntop($row['IP']),
				(array_key_exists('learningMode', $actionData) ? $actionData['learningMode'] : 0),
				(array_key_exists('paramKey', $actionData) ? $actionData['paramKey'] : false),
				(array_key_exists('paramValue', $actionData) ? $actionData['paramValue'] : false),
				(array_key_exists('failedRules', $actionData) ? $actionData['failedRules'] : ''),
				strpos($row['URL'], 'https') === 0 ? 1 : 0,
				(array_key_exists('fullRequest', $actionData) ? $actionData['fullRequest'] : ''),
			);
		}
		return wfWAFUtils::json_encode($data);
	}

	/**
	 * Get all attack data in array format.
	 */
	public function getAttackDataArray() {
		return $this->getNewestAttackDataArray(floatval($this->getConfig('lastAttackDataTruncateTime')));
	}

	/**
	 * Get attack data after a certain timestamp in array format.
	 *
	 * @param int $newerThan
	 * @return array
	 */
	public function getNewestAttackDataArray($newerThan) {
		$table = $this->networkTable('wfHits');
		$results = $this->db->get_results('SELECT * FROM ' . $table . ' WHERE attackLogTime > ?', array(
			$newerThan,
		));

		$data = array();
		foreach ($results as $row) {
			$actionData = wfWAFUtils::json_decode($row['actionData'], true);
			if (!is_array($actionData))
				$actionData = array();
			$data[] = array(
				$row['attackLogTime'],
				$row['ctime'],
				wfWAFUtils::inet_ntop($row['IP']),
				(array_key_exists('learningMode', $actionData) ? $actionData['learningMode'] : 0),
				(array_key_exists('paramKey', $actionData) ? base64_decode($actionData['paramKey']) : false),
				(array_key_exists('paramValue', $actionData) ? base64_decode($actionData['paramValue']) : false),
				(array_key_exists('failedRules', $actionData) ? $actionData['failedRules'] : ''),
				strpos($row['URL'], 'https') === 0 ? 1 : 0,
				(array_key_exists('fullRequest', $actionData) ? base64_decode($actionData['fullRequest']) : ''),
				(array_key_exists('requestMetadata', $actionData) ? $actionData['requestMetadata'] : ''),
				$row['id'],
			);
		}
		return $data;
	}

	/**
	 * I don't think this will be needed for what it's used for in the plugin.
	 */
	public function truncateAttackData() {
		$this->setConfig('lastAttackDataTruncateTime', microtime(true));
		return true;
	}

	/**
	 * Insert request into wfHits.
	 *
	 * @param array $failedRules
	 * @param string $failedParamKey
	 * @param string $failedParamValue
	 * @param wfWAFRequestInterface $request
	 * @param mixed $_
	 * @return mixed
	 */
	public function logAttack($failedRules, $failedParamKey, $failedParamValue, $request, $_ = null) {
		$table = $this->networkTable('wfHits');

		$failedRulesString = '';
		if (is_array($failedRules)) {
			/**
			 * @var int $index
			 * @var wfWAFRule|int $rule
			 */
			foreach ($failedRules as $index => $rule) {
				if ($rule instanceof wfWAFRule) {
					$failedRulesString .= $rule->getRuleID() . '|';
				} else {
					$failedRulesString .= $rule . '|';
				}
			}
			$failedRulesString = wfWAFUtils::substr($failedRulesString, 0, -1);
		}
		if (preg_match('/\blogged\b/i', $failedRulesString)) {
			$statusCode = 200;
			$action = 'logged:waf';
		} else {
			$statusCode = 403;
			$action = 'blocked:waf';
		}

		$ua = '';
		$referer = '';
		$headers = $request->getHeaders();
		if (is_array($headers)) {
			if (array_key_exists('User-Agent', $headers)) {
				$ua = $headers['User-Agent'];
			}
			if (array_key_exists('Referer', $headers)) {
				$referer = $headers['Referer'];
			}
		}

		$attackData = array(
				'failedRules'     => $failedRulesString,
				'paramKey'        => base64_encode((string) $failedParamKey),
				'paramValue'      => base64_encode((string) $failedParamValue),
				'path'            => base64_encode($request->getPath()),
				'fullRequest'     => base64_encode($request),
				'requestMetadata' => $request->getMetadata(),
		);
		$attackDataJson = wfWAFUtils::json_encode_limited($attackData, 65535, array('fullRequest', 'paramValue'));

		$row = array(
			'attackLogTime'     => microtime(true),
			'ctime'             => $request->getTimestamp(),
			'IP'                => wfWAFUtils::inet_pton($request->getIP()),
			'statusCode'        => $statusCode,
			'URL'               => $request->getProtocol() . '://' . $request->getHost() . $request->getURI(),
			'isGoogle'          => 0,
			'userID'            => 0,
			'newVisit'          => 0,
			'referer'           => $referer,
			'UA'                => $ua,
			'action'            => $action,
			'actionData'        => $attackDataJson
		);

		try {
			return $this->db->insert($table, $row);
		} catch (wfWAFStorageEngineMySQLiException $e) { // Let the firewall block the request without logging.
			error_log('Failed to log attack data: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Insert IP into wfBlocks.
	 *
	 * @param float $timestamp
	 * @param string $ip
	 * @param int $type
	 * @return mixed
	 */
	public function blockIP($timestamp, $ip, $type = wfWAFStorageInterface::IP_BLOCKS_SINGLE) {
		$blockedIPs = $this->getConfig('wfWAFBlockedIPs');
		if (!$blockedIPs) {
			$blockedIPs = array();
		}
		$blockedIPs[$ip] = array($timestamp, $type);
		$this->setConfig('wfWAFBlockedIPs', $blockedIPs);
		return true;
	}

	/**
	 * Check if the IP is in wfBlocks.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function isIPBlocked($ip) {
		$blockedIPs = $this->getConfig('wfWAFBlockedIPs');
		if (!$blockedIPs) {
			$blockedIPs = array();
		}
		return array_key_exists($ip, $blockedIPs) && is_array($blockedIPs[$ip]) && $blockedIPs[$ip][0] >= time();
	}

	/**
	 * Remove all blocked IPs.
	 *
	 * @param int $types
	 */
	public function purgeIPBlocks($types = wfWAFStorageInterface::IP_BLOCKS_ALL) {
		if ($types === wfWAFStorageInterface::IP_BLOCKS_ALL) {
			$this->unsetConfig('wfWAFBlockedIPs');
		} else {
			$blockedIPs = $this->getConfig('wfWAFBlockedIPs');
			if (!$blockedIPs) {
				$blockedIPs = array();
			}
			foreach ($blockedIPs as $key => $values) {
				list($timestamp, $type) = $values;
				if (($type & $types) > 0 || $timestamp < time()) {
					unset($blockedIPs[$key]);
				}
			}
			$this->setConfig('wfWAFBlockedIPs', $blockedIPs);
		}
	}

	/**
	 * Query config item from wfConfig table.
	 *
	 * @param $key
	 * @param null $default
	 * @param string $category
	 * @return mixed
	 */
	public function getConfig($key, $default = null, $category = '') {
		if (!$this->data) {
			$this->autoloadConfig();
		}

		if (array_key_exists($category, $this->data) && array_key_exists($key, $this->data[$category])) {
			if (!isset($this->serializedStatus[$key]) && in_array($key, $this->getSerializedParams())) { //Value is still serialized from the autoload, finish deserializing
				$value = @unserialize($this->data[$category][$key]);
				$this->data[$category][$key] = $value;
				$this->serializedStatus[$key] = true;
			}
			return $this->data[$category][$key];
		}

		$table = $this->getStorageTable($category);
		$val = $this->db->get_var('SELECT val FROM ' . $table . ' WHERE name = ?', array(
			$key,
		));
		if ($val !== null) {
			if (in_array($key, $this->getSerializedParams())) {
				$value = @unserialize($val);
				$this->data[$category][$key] = $value;
				$this->serializedStatus[$key] = true;
				return $value;
			}
			$this->data[$category][$key] = $val;
			return $val;
		}
		return $default;
	}

	/**
	 * Insert/update wfConfig table for WAF option.
	 *
	 * @param $key
	 * @param $value
	 * @param string $category
	 */
	public function setConfig($key, $value, $category = '') {
		if (!array_key_exists($category, $this->data)) {
			$this->data[$category] = array();
		}

		$changedConfigValue = (array_key_exists($key, $this->data[$category]) && $this->data[$category][$key] != $value) ||
			!array_key_exists($key, $this->data[$category]);

		if (!$this->dataChanged && $changedConfigValue) {
			$this->dataChanged = array($category, $key, true);
			$this->shutdownRegistry->register(array($this, 'saveConfig'), wfShutdownRegistry::PRIORITY_LAST);
		}
		if ($changedConfigValue) {
			$this->dataToSave[$category][$key] = $value;
		}

		$this->data[$category][$key] = $value;
		if (in_array($key, $this->getSerializedParams())) {
			$this->serializedStatus[$key] = true;
		}
	}

	/**
	 * Delete config item from wfConfig table.
	 *
	 * @param $key
	 * @param string $category
	 */
	public function unsetConfig($key, $category = '') {
		unset($this->data[$category][$key]);
		$table = $this->getStorageTable($category);
		$this->db->delete($table, array(
			'name' => $key,
		));
	}

	/**
	 *
	 */
	public function saveConfig() {
		if ($this->uninstalled) {
			return;
		}

		try {
			foreach ($this->dataToSave as $category => $data) {
				foreach ($data as $key => $value) {
					if (in_array($key, $this->getSerializedParams())) {
						if (isset($this->serializedStatus[$key])) {
							$value = serialize($value);
						}
					}
					$table = $this->getStorageTable($category);
					$this->db->query("INSERT INTO {$table} (name, val, autoload) values (?, ?, 'no') ON DUPLICATE KEY UPDATE val = ?", array(
						$key,
						$value,
						$value,
					));
				}
			}
		} catch (wfWAFStorageEngineMySQLiException $e) {
			if (WFWAF_DEBUG) {
				error_log($e);
			}
		}
	}

	/**
	 * Remove related WAF specific configuration.
	 */
	public function uninstall() {
		try {
			$this->getDb()->query("DROP TABLE IF EXISTS " . $this->networkTable('wfwafconfig'));
		} catch (wfWAFStorageEngineMySQLiException $e) {
			error_log($e);
		}
		$this->uninstalled = true;
	}

	/**
	 * Pull from wfConfig.
	 */
	public function isInLearningMode() {
		if ($this->getConfig('wafStatus', '') == 'learning-mode') {
			if ($this->getConfig('learningModeGracePeriodEnabled', false)) {
				if ($this->getConfig('learningModeGracePeriod', 0) > time()) {
					return true;
				} else {
					// Reached the end of the grace period, activate the WAF.
					$this->setConfig('wafStatus', 'enabled');
					$this->setConfig('learningModeGracePeriodEnabled', 0);
					$this->unsetConfig('learningModeGracePeriod');
				}
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Pull from wfConfig.
	 */
	public function isDisabled() {
		return $this->getConfig('wafStatus', '') === 'disabled' || $this->getConfig('wafDisabled', 0);
	}

	/**
	 * Return hardcoded path maybe?
	 */
	public function getRulesDSLCacheFile() {

	}

	/**
	 * Probably not.
	 */
	public function isAttackDataFull() {
		return false;
	}

	/**
	 *
	 */
	public function vacuum() {
		$this->purgeIPBlocks(wfWAFStorageInterface::IP_BLOCKS_ALL);
	}

	/**
	 * @return wfWAFStorageEngineDatabase
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 *
	 */
	public function setDefaults() {
		$defaults = $this->getDefaultConfiguration();
		foreach ($defaults as $key => $value) {
			$val = $this->getConfig($key);
			if ($val === null) {
				$this->setConfig($key, $value);
			}
		}
	}

	/**
	 *
	 */
	public function runMigrations() {
//		$currentVersion = $this->getConfig('version');
//		if (!$currentVersion || version_compare($currentVersion, WFWAF_VERSION) === -1) {
//
//		}

		$this->getDb()->query("CREATE TABLE IF NOT EXISTS " . $this->networkTable('wfwafconfig') .
			" (
	`name` varchar(100) NOT NULL,
	`val` longblob,
	`autoload` enum('no','yes') NOT NULL DEFAULT 'yes',
	PRIMARY KEY (`name`)
) DEFAULT CHARSET=utf8
");
	}

	/**
	 * @return array
	 */
	public function getDefaultConfiguration() {
		return array(
			'wafStatus'                      => 'learning-mode',
			'learningModeGracePeriodEnabled' => 1,
			'learningModeGracePeriod'        => time() + (86400 * 7),
			'authKey'                        => wfWAFUtils::getRandomString(64),
		);
	}

	/**
	 * @return array
	 */
	public function getSerializedParams() {
		return array(
			'cron',
			'whitelistedURLParams',
			'disabledRules',
			'wfWAFBlockedIPs',
			'wafRules',
		);
	}

	/**
	 * @return array
	 */
	public function getAutoloadParams() {
		return array(
			''          => array(
				'wafStatus',
				'learningModeGracePeriodEnabled',
				'learningModeGracePeriod',
				'authKey',
				'version',
				'advancedBlockingEnabled',
				'disabledRules',
				'patternBlocks',
				'countryBlocks',
				'otherBlocks',
				'lockouts',
				'wafRules',
				'avoid_php_input',
				'wafDisabled',
				'wfWAFBlockedIPs',
				'disableWAFBlacklistBlocking',
			),
			'livewaf'   => array(
				'cron',
				'whitelistedURLParams',
				'whitelistedURLs',
			),
			'transient' => array(
				'watchedIPs',
				'blockedPrefixes',
			),
			'synced'    => array(
				'timeoffset_wf',
				'apiKey',
				'isPaid',
				'siteURL',
				'homeURL',
				'whitelistedIPs',
				'howGetIPs',
				'howGetIPs_trusted_proxies',
				'howGetIPs_trusted_proxies_unified',
				'other_WFNet',
				'pluginABSPATH',
				'serverIPs',
				'disableWAFIPBlocking',
				'advancedBlockingEnabled',
				'blockCustomText',
				'whitelistedServiceIPs'
			),
		);
	}

	protected function autoloadConfig() {
		$params = $this->getAutoloadParams();
		foreach ($params as $category => $autoloadParams) {
			// Set default keys to null to prevent re-querying the table for config keypairs that aren't in the table.
			foreach ($autoloadParams as $autoloadParam) {
				$this->data[$category][$autoloadParam] = null;
			}

			$table = $this->getStorageTable($category);
			$whereIn = str_repeat('?,', count($autoloadParams) - 1) . '?';
			$results = $this->db->get_results('SELECT * FROM ' . $table . ' WHERE name IN (' . $whereIn . ')', $autoloadParams);
			foreach ($results as $row) {
				$this->data[$category][$row['name']] = $row['val'];
			}
		}
	}

	public function getRules() {
		return $this->getConfig('wafRules');
	}

	public function setRules($rules) {
		$this->setConfig('wafRules', $rules);
	}

	public function needsInitialRules() {
		$rules = $this->getRules();
		return !$rules;
	}

	public function getStorageTable($category) {
		switch ($category) {
			case 'livewaf':
			case 'transient':
				$table = $this->networkTable('wfwafconfig');
				break;
			default:
				$table = $this->networkTable('wfConfig');
				break;
		}
		return $table;
	}

	public function getDescription() {
		return __('mysqli', 'wordfence');
	}

}

interface wfWAFStorageEngineDatabase {

	public function connect($user, $password, $database, $host, $port = null, $socket = null, $flags = 0);

	public function setCharset($charset, $collation);

	public function close();

	public function insert($table, $data);

	public function update($table, $data, $where);

	public function delete($table, $where);

	public function query($sql, $data = array());

	public function get_var($query = null, $data = array(), $x = 0, $y = 0);

	public function get_row($query = null, $data = array(), $y = 0);

	public function get_results($query = null, $data = array());
}

class wfWAFStorageEngineMySQLi implements wfWAFStorageEngineDatabase {

	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var string
	 */
	private $password;
	/**
	 * @var string
	 */
	private $database;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var int|null
	 */
	private $port;
	/**
	 * @var string|null
	 */
	private $socket;

	/** @var mysqli */
	private $dbh;

	private $lastStatement;

	public $installing = false;

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 * @param string $user
	 * @param string $password
	 * @param string $database
	 * @param string $host
	 * @param null|int $port
	 * @param mixed $socket
	 * @return mysqli
	 * @throws wfWAFStorageEngineMySQLiException
	 */
	public function connect($user, $password, $database, $host, $port = null, $socket = null, $flags = 0, $sslOptions = null) {
		$this->dbh = mysqli_init();
		if (!empty($sslOptions) && ($flags & (MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT))) {
			mysqli_ssl_set(
				$this->dbh,
				array_key_exists('key', $sslOptions) ? $sslOptions['key'] : null,
				array_key_exists('certificate', $sslOptions) ? $sslOptions['certificate'] : null,
				array_key_exists('ca_certificate', $sslOptions) ? $sslOptions['ca_certificate'] : null,
				array_key_exists('ca_path', $sslOptions) ? $sslOptions['ca_path'] : null,
				array_key_exists('cipher_algos', $sslOptions) ? $sslOptions['cipher_algos'] : null
			);
		}
		if (!@mysqli_real_connect($this->dbh, $host, $user, $password, $database, $port, $socket, $flags)) {
			$error = error_get_last();
			throw new wfWAFStorageEngineMySQLiException('Unable to connect to database: ' . $error['message'], $error['type']);
		}

		return $this->dbh;
	}

	public function setCharset($charset, $collation) {
		$result = $this->determineCharset($charset, $collation);
		$charset = $result['charset'];
		$collation = $result['collation'];
		$this->setConnectionCharset($charset, $collation);
	}

	protected function determineCharset($charset, $collation) {
		if ('utf8' === $charset && $this->hasCap('utf8mb4')) {
			$charset = 'utf8mb4';
		}

		if ('utf8mb4' === $charset && !$this->hasCap('utf8mb4')) {
			$charset = 'utf8';
			$collation = str_replace('utf8mb4_', 'utf8_', $collation);
		}

		if ('utf8mb4' === $charset) {
			// _general_ is outdated, so we can upgrade it to _unicode_, instead.
			if (!$collation || 'utf8_general_ci' === $collation) {
				$collation = 'utf8mb4_unicode_ci';
			} else {
				$collation = str_replace('utf8_', 'utf8mb4_', $collation);
			}
		}

		// _unicode_520_ is a better collation, we should use that when it's available.
		if ($this->hasCap('utf8mb4_520') && 'utf8mb4_unicode_ci' === $collation) {
			$collation = 'utf8mb4_unicode_520_ci';
		}

		return compact('charset', 'collation');
	}

	/**
	 * Determine if a database supports a particular feature.
	 *
	 * @param string $dbCap The feature to check for. Accepts 'collation',
	 *                       'group_concat', 'subqueries', 'set_charset',
	 *                       'utf8mb4', or 'utf8mb4_520'.
	 * @return int|false Whether the database feature is supported, false otherwise.
	 */
	public function hasCap($dbCap) {
		$version = $this->dbVersion();

		switch (strtolower($dbCap)) {
			case 'collation' :    // @since 2.5.0
			case 'group_concat' : // @since 2.7.0
			case 'subqueries' :   // @since 2.7.0
				return version_compare($version, '4.1', '>=');
			case 'set_charset' :
				return version_compare($version, '5.0.7', '>=');
			case 'utf8mb4' :      // @since 4.1.0
				if (version_compare($version, '5.5.3', '<')) {
					return false;
				}
				$client_version = mysqli_get_client_info();

				/*
				 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
				 * mysqlnd has supported utf8mb4 since 5.0.9.
				 */
				if (false !== strpos($client_version, 'mysqlnd')) {
					$client_version = preg_replace('/^\D+([\d.]+).*/', '$1', $client_version);
					return version_compare($client_version, '5.0.9', '>=');
				} else {
					return version_compare($client_version, '5.5.3', '>=');
				}
			case 'utf8mb4_520' : // @since 4.6.0
				return version_compare($version, '5.6', '>=');
		}

		return false;
	}

	public function setConnectionCharset($charset, $collation) {
		if ($this->hasCap('collation') && !empty($charset)) {
			$setCharsetSucceeded = false;

			if (function_exists('mysqli_set_charset') && $this->hasCap('set_charset')) {
				$setCharsetSucceeded = mysqli_set_charset($this->dbh, $charset);
			}

			if ($setCharsetSucceeded) {
				$query = "SET NAMES {$this->escape($charset)}";
				if ($collation) {
					$query .= " COLLATE {$this->escape($collation)}";
				}
				$this->query($query);
			}
		}
	}

	/**
	 * Retrieves the MySQL server version.
	 *
	 * @return null|string Null on failure, version number on success.
	 */
	public function dbVersion() {
		$serverInfo = mysqli_get_server_info($this->dbh);
		return preg_replace('/[^0-9.].*/', '', $serverInfo);
	}

	/**
	 *
	 */
	public function close() {
		mysqli_close($this->dbh);
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return bool|int|string
	 */
	public function insert($table, $data) {
		$sql = $this->buildInsertSQL($table, $data);
		if ($stmt = $this->query($sql, $data)) {
			$insertID = mysqli_insert_id($this->dbh);
			$stmt->close();
			return $insertID;
		}
		return false;
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param array $where
	 * @return bool|int
	 * @throws wfWAFStorageEngineMySQLiException
	 */
	public function update($table, $data, $where) {
		if (!$data) {
			throw new wfWAFStorageEngineMySQLiException('Values to update must supplied to \wfWAFStorageEngineMySQLi::update.');
		}
		if (!$where) {
			throw new wfWAFStorageEngineMySQLiException('A where clause must supplied to \wfWAFStorageEngineMySQLi::update.');
		}
		$sql = $this->buildUpdateSQL($table, $data, $where);
		if ($stmt = $this->query($sql, array_merge(array_values($data), array_values($where)))) {
			$affectedRows = mysqli_affected_rows($this->dbh);
			$stmt->close();
			return $affectedRows;
		}
		return false;

	}

	/**
	 * @param string $table
	 * @param array $where
	 * @return bool|int
	 * @throws wfWAFStorageEngineMySQLiException
	 */
	public function delete($table, $where) {
		if (!$where) {
			throw new wfWAFStorageEngineMySQLiException('A where clause must supplied to \wfWAFStorageEngineMySQLi::delete.');
		}
		$sql = $this->buildDeleteSQL($table, $where);
		if ($stmt = $this->query($sql, $where)) {
			$affectedRows = mysqli_affected_rows($this->dbh);
			$stmt->close();
			return $affectedRows;
		}
		return false;
	}

	/**
	 * @param $sql
	 * @param array $data
	 * @return mysqli_stmt
	 * @throws wfWAFStorageEngineMySQLiException
	 */
	public function query($sql, $data = array()) {
		if ($this->installing) {
			return false;
		}

		$stmt = mysqli_prepare($this->dbh, $sql);
		if (!$stmt) {
			throw new wfWAFStorageEngineMySQLiException(
				sprintf('MySQL error[%d]: %s', mysqli_errno($this->dbh), mysqli_error($this->dbh)),
				mysqli_errno($this->dbh)
			);
		}

		$bindFormats = '';
		$bindData = array();
		$bindCounter = 0;
		foreach ($data as $value) {
			switch (gettype($value)) {
				case 'integer':
				case 'boolean':
					$bindFormats .= 'i';
					${"bindVar{$bindCounter}"} = (int) $value;
					$bindData[] = &${"bindVar{$bindCounter}"};
					break;

				case 'string':
					$bindFormats .= 's';
					${"bindVar{$bindCounter}"} = $value;
					$bindData[] = &${"bindVar{$bindCounter}"};
					break;

				case 'double':
				case 'float':
					$bindFormats .= 'd';
					${"bindVar{$bindCounter}"} = $value;
					$bindData[] = &${"bindVar{$bindCounter}"};
					break;

				default:
					$bindFormats .= 'b';
					${"bindVar{$bindCounter}"} = $value;
					$bindData[] = &${"bindVar{$bindCounter}"};
					break;
			}
			$bindCounter++;
		}

		if ($bindData) {
			array_unshift($bindData, $bindFormats);
			call_user_func_array(array($stmt, 'bind_param'), $bindData);
		}

		$stmt->execute();
		if ($stmt->errno > 0) {
			throw new wfWAFStorageEngineMySQLiException('MySQL error [' . $stmt->errno . ']: ' . $stmt->error, $stmt->errno);
		}

		return $stmt;
	}

	/**
	 * @param mysqli_stmt $stmt
	 * @return array
	 */
	public function statementToArray($stmt) {
		if (!$stmt) {
			return array();
		}

		$result = $stmt->get_result();

		$return = array();
		while ($row = $result->fetch_array(MYSQLI_BOTH)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	 * @param string $query
	 * @param array $data
	 * @param int $x
	 * @param int $y
	 * @return null|mixed
	 */
	public function get_var($query = null, $data = array(), $x = 0, $y = 0) {
		$this->lastStatement = $this->query($query, $data);
		$results = $this->statementToArray($this->lastStatement);

		if (isset($results[$y][$x])) {
			return $results[$y][$x];
		}

		return null;
	}

	/**
	 * @param string $query
	 * @param array $data
	 * @param int $y
	 * @return mixed|null
	 */
	public function get_row($query = null, $data = array(), $y = 0) {
		$stmt = $this->query($query, $data);
		$results = $this->statementToArray($stmt);

		if (isset($results[$y])) {
			return $results[$y];
		}

		return null;
	}

	/**
	 * @param string $query
	 * @param array $data
	 * @return array
	 */
	public function get_results($query = null, $data = array()) {
		$stmt = $this->query($query, $data);
		return $this->statementToArray($stmt);
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function escape($value) {
		return sprintf("'%s'", mysqli_real_escape_string($this->dbh, $value));
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	protected function buildInsertSQL($table, $data) {
		$columns = array();
		$values = array();
		foreach ($data as $column => $value) {
			$columns[] = $this->sanitizeColumn($column);
			$values[] = '?';
		}
		$sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, join(',', $columns), join(',', $values));
		return $sql;
	}

	/**
	 * @param string $column
	 * @return mixed
	 */
	protected function sanitizeColumn($column) {
		return preg_replace('/[^a-zA-Z0-9_]/i', '', $column);
	}

	/**
	 * @return mixed
	 */
	public function getLastStatement() {
		return $this->lastStatement;
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @return string
	 */
	protected function buildDeleteSQL($table, $where) {
		$sql = sprintf('DELETE FROM %s %s', $table, $this->buildWhereClause($where));
		return $sql;
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @param array $where
	 * @return string
	 */
	protected function buildUpdateSQL($table, $data, $where) {
		if (!is_array($data)) {
			throw new InvalidArgumentException('Argument 2 expected to be array. ' . gettype($data) . ' given.');
		}
		if (count($data) === 0) {
			throw new InvalidArgumentException('Argument 2 cannot be empty.');
		}
		if (!is_array($where)) {
			throw new InvalidArgumentException('Argument 3 expected to be array. ' . gettype($where) . ' given.');
		}
		if (count($where) === 0) {
			throw new InvalidArgumentException('Argument 3 cannot be empty.');
		}

		return sprintf('UPDATE %s SET %s %s', $table, $this->buildUpdateClause($data), $this->buildWhereClause($where));
	}

	/**
	 * @param array $where
	 * @return string
	 */
	protected function buildWhereClause($where) {
		if (!is_array($where)) {
			throw new InvalidArgumentException('Argument 1 expected to be array. ' . gettype($where) . ' given.');
		}
		if (!$where) {
			return '';
		}
		$sql = 'WHERE ';
		foreach ($where as $column => $value) {
			$sql .= $this->sanitizeColumn($column) . ' = ? AND ';
		}
		return wfWAFUtils::substr($sql, 0, -5);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	protected function buildUpdateClause($data) {
		if (!is_array($data)) {
			throw new InvalidArgumentException('Argument 1 expected to be array. ' . gettype($data) . ' given.');
		}
		if (!$data) {
			throw new InvalidArgumentException('Argument 1 cannot be an empty array.');
		}
		$sql = '';
		foreach ($data as $column => $value) {
			$sql .= $this->sanitizeColumn($column) . ' = ?, ';
		}
		return wfWAFUtils::substr($sql, 0, -2);
	}
}


class wfWAFStorageEngineMySQLiException extends wfWAFException {

}