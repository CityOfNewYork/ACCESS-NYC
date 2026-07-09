<?php
require_once(dirname(__FILE__) . '/wfDB.php');
class wfSchema {
	const TABLE_CASE_OPTION = 'wordfence_case'; //false is camel case, true is lower
	
	private static $_usingLowercase = null;
	private static $deprecatedTables = array(
		'wfBlocks',
		'wfBlocksAdv',
		'wfLockedOut',
		'wfThrottleLog',
		'wfNet404s',
		'wfBlockedCommentLog',
		'wfVulnScanners',
		'wfBadLeechers',
		'wfLeechers',
		'wfScanners',
	);
	
	private static $tables = array(
"wfAuditEvents" => "(
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  `event_time` double(14,4) NOT NULL,
  `request_id` bigint(20) unsigned NOT NULL,
  `state` enum('new','sending','sent') NOT NULL DEFAULT 'new',
  `state_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",
"wfSecurityEvents" => "(
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  `event_time` double(14,4) NOT NULL,
  `state` enum('new','sending','sent') NOT NULL DEFAULT 'new',
  `state_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",
"wfBlocks7" => "(
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  `IP` binary(16) NOT NULL DEFAULT '\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0\\\0',
  `blockedTime` bigint(20) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `lastAttempt` int(10) unsigned DEFAULT '0',
  `blockedHits` int(10) unsigned DEFAULT '0',
  `expiration` bigint(20) unsigned NOT NULL DEFAULT '0',
  `parameters` text,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `IP` (`IP`),
  KEY `expiration` (`expiration`)
) DEFAULT CHARSET=utf8",
"wfConfig" => "(
  `name` varchar(100) NOT NULL,
  `val` longblob,
  `autoload` enum('no','yes') NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`name`)
) DEFAULT CHARSET=utf8",
"wfCrawlers" => "(
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `patternSig` binary(16) NOT NULL,
  `status` char(8) NOT NULL,
  `lastUpdate` int(10) unsigned NOT NULL,
  `PTR` varchar(255) DEFAULT '',
  PRIMARY KEY (`IP`,`patternSig`)
) DEFAULT CHARSET=utf8",
"wfFileChanges" => "(
  `filenameHash` char(64) NOT NULL,
  `file` varchar(1000) NOT NULL,
  `md5` char(32) NOT NULL,
  PRIMARY KEY (`filenameHash`)
) CHARSET=utf8",
"wfHits" => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attackLogTime` double(17,6) unsigned NOT NULL,
  `ctime` double(17,6) unsigned NOT NULL,
  `IP` binary(16) DEFAULT NULL,
  `jsRun` tinyint(4) DEFAULT '0',
  `statusCode` int(11) NOT NULL DEFAULT '200',
  `isGoogle` tinyint(4) NOT NULL,
  `userID` int(10) unsigned NOT NULL,
  `newVisit` tinyint(3) unsigned NOT NULL,
  `URL` text,
  `referer` text,
  `UA` text,
  `action` varchar(64) NOT NULL DEFAULT '',
  `actionDescription` text,
  `actionData` text,
  PRIMARY KEY (`id`),
  KEY `k1` (`ctime`),
  KEY `k2` (`IP`,`ctime`),
  KEY `attackLogTime` (`attackLogTime`)
) DEFAULT CHARSET=utf8",
"wfIssues" => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `lastUpdated` int(10) unsigned NOT NULL,
  `status` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `severity` tinyint(3) unsigned NOT NULL,
  `ignoreP` char(32) NOT NULL,
  `ignoreC` char(32) NOT NULL,
  `shortMsg` varchar(255) NOT NULL,
  `longMsg` text,
  `data` text,
  PRIMARY KEY (`id`),
  KEY `lastUpdated` (`lastUpdated`),
  KEY `status` (`status`),
  KEY `ignoreP` (`ignoreP`),
  KEY `ignoreC` (`ignoreC`)
) DEFAULT CHARSET=utf8",
"wfPendingIssues" => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` int(10) unsigned NOT NULL,
  `lastUpdated` int(10) unsigned NOT NULL,
  `status` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `severity` tinyint(3) unsigned NOT NULL,
  `ignoreP` char(32) NOT NULL,
  `ignoreC` char(32) NOT NULL,
  `shortMsg` varchar(255) NOT NULL,
  `longMsg` text,
  `data` text,
  PRIMARY KEY (`id`),
  KEY `lastUpdated` (`lastUpdated`),
  KEY `status` (`status`),
  KEY `ignoreP` (`ignoreP`),
  KEY `ignoreC` (`ignoreC`)
) DEFAULT CHARSET=utf8",
"wfTrafficRates" => "(
  `eMin` int(10) unsigned NOT NULL,
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `hitType` enum('hit','404') NOT NULL DEFAULT 'hit',
  `hits` int(10) unsigned NOT NULL,
  PRIMARY KEY (`eMin`,`IP`,`hitType`)
) DEFAULT CHARSET=utf8",
"wfLocs" => "(
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `ctime` int(10) unsigned NOT NULL,
  `failed` tinyint(3) unsigned NOT NULL,
  `city` varchar(255) DEFAULT '',
  `region` varchar(255) DEFAULT '',
  `countryName` varchar(255) DEFAULT '',
  `countryCode` char(2) DEFAULT '',
  `lat` float(10,7) DEFAULT '0.0000000',
  `lon` float(10,7) DEFAULT '0.0000000',
  PRIMARY KEY (`IP`)
) DEFAULT CHARSET=utf8",
"wfLogins" => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hitID` int(11) DEFAULT NULL,
  `ctime` double(17,6) unsigned NOT NULL,
  `fail` tinyint(3) unsigned NOT NULL,
  `action` varchar(40) NOT NULL,
  `username` varchar(255) NOT NULL,
  `userID` int(10) unsigned NOT NULL,
  `IP` binary(16) DEFAULT NULL,
  `UA` text,
  PRIMARY KEY (`id`),
  KEY `k1` (`IP`,`fail`),
  KEY `hitID` (`hitID`)
) DEFAULT CHARSET=utf8",
"wfReverseCache" => "(
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `host` varchar(255) NOT NULL,
  `lastUpdate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`IP`)
) DEFAULT CHARSET=utf8",
"wfStatus" => "(
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ctime` double(17,6) unsigned NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `type` char(5) NOT NULL,
  `msg` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `k1` (`ctime`),
  KEY `k2` (`type`)
) DEFAULT CHARSET=utf8",
'wfHoover' => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner` text,
  `host` text,
  `path` text,
  `hostKey` varbinary(124) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `k2` (`hostKey`)
) DEFAULT CHARSET=utf8",
'wfFileMods' => "(
  `filenameMD5` binary(16) NOT NULL,
  `filename` varchar(1000) NOT NULL,
  `knownFile` tinyint(3) unsigned NOT NULL,
  `oldMD5` binary(16) NOT NULL DEFAULT '',
  `newMD5` binary(16) NOT NULL,
  `SHAC` binary(32) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `stoppedOnSignature` varchar(255) NOT NULL DEFAULT '',
  `stoppedOnPosition` int(10) unsigned NOT NULL DEFAULT '0',
  `isSafeFile` varchar(1) NOT NULL DEFAULT '?',
  PRIMARY KEY (`filenameMD5`)
) DEFAULT CHARSET=utf8",
'wfBlockedIPLog' => "(
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `countryCode` varchar(2) NOT NULL,
  `blockCount` int(10) unsigned NOT NULL DEFAULT '0',
  `unixday` int(10) unsigned NOT NULL,
  `blockType` varchar(50) NOT NULL DEFAULT 'generic',
  PRIMARY KEY (`IP`,`unixday`,`blockType`)
) DEFAULT CHARSET=utf8",
'wfSNIPCache' => "(
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `IP` varchar(45) NOT NULL DEFAULT '',
  `expiration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `body` varchar(255) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `expiration` (`expiration`),
  KEY `IP` (`IP`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8",
'wfKnownFileList' => "(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `path` text NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8",
'wfNotifications' => "(
  `id` varchar(32) NOT NULL DEFAULT '',
  `new` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `category` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '1000',
  `ctime` int(10) unsigned NOT NULL,
  `html` text NOT NULL,
  `links` text NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;",
'wfLiveTrafficHuman' => "(
  `IP` binary(16) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `identifier` binary(32) NOT NULL DEFAULT '\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0',
  `expiration` int(10) unsigned NOT NULL,
  PRIMARY KEY (`IP`,`identifier`),
  KEY `expiration` (`expiration`)
) DEFAULT CHARSET=utf8;",
'wfWafFailures' => "(
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `throwable` TEXT NOT NULL,
  `rule_id` INT(10) UNSIGNED,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8"
);
	private $db = false;
	public function __construct($dbhost = false, $dbuser = false, $dbpassword = false, $dbname = false){
		$this->db = new wfDB();
	}
	public function dropAll(){
		foreach(self::$tables as $table => $def) {
			$originalTable = wfDB::networkPrefix() . $table;
			$convertedTable = wfDB::networkPrefix() . strtolower($table);
			
			$this->db->queryWrite("DROP TABLE IF EXISTS {$convertedTable}");
			$this->db->queryWrite("DROP TABLE IF EXISTS {$originalTable}");
		}
		
		foreach (self::$deprecatedTables as $table) {
			$originalTable = wfDB::networkTable($table, false);
			$convertedTable = wfDB::networkTable($table);
			
			$this->db->queryWrite("DROP TABLE IF EXISTS {$convertedTable}");
			if ($originalTable !== $convertedTable) {
				$this->db->queryWrite("DROP TABLE IF EXISTS {$originalTable}");
			}
		}
	}
	public function createAll() {
		foreach(self::$tables as $table => $def){
			$this->db->queryWrite("CREATE TABLE IF NOT EXISTS " . wfDB::networkTable($table) . " " . $def);
		}
	}
	public function create($table) {
		$this->db->queryWrite("CREATE TABLE IF NOT EXISTS " . wfDB::networkTable($table) . " " . self::$tables[$table]);
	}
	public function drop($table) {
		$originalTable = wfDB::networkTable($table, false);
		$convertedTable = wfDB::networkTable($table);
		
		$this->db->queryWrite("DROP TABLE IF EXISTS {$convertedTable}");
		if ($originalTable !== $convertedTable) {
			$this->db->queryWrite("DROP TABLE IF EXISTS {$originalTable}");
		}
	}
	
	/**
	 * Returns an array of all required table names. The key is the table name before applying the configured 
	 * WordPress/network prefixing, and its corresponding value is with that applied.
	 * 
	 * @return array
	 */
	public static function tableList() {
		$rawTables = array_keys(self::$tables);
		if (WFWAF_IS_WINDOWS || wfSchema::usingLowercase()) {
			$rawTables = wfUtils::array_strtolower($rawTables);
		}
		$tableList = array_map(function($t) { return wfDB::networkTable($t); }, array_combine($rawTables, $rawTables));
		
		foreach (
			array(
				\WordfenceLS\Controller_DB::TABLE_2FA_SECRETS,
				\WordfenceLS\Controller_DB::TABLE_SETTINGS,
				\WordfenceLS\Controller_DB::TABLE_ROLE_COUNTS,
			) as $t) {
			$table = \WordfenceLS\Controller_DB::network_table($t);
			$tableList[$t] = $table;
		}
		
		return $tableList;
	}
	
	/**
	 * Like `tableList()` but returns only optional tables that may or may not exist depending on the site's 
	 * configuration.
	 * 
	 * @return array
	 */
	public static function optionalTableList() {
		return array('wfwafconfig' => wfDB::networkTable('wfwafconfig'));
	}
	
	public static function updateTableCase() {
		global $wpdb;
		$hasCamelCaseTable = !!$wpdb->get_var($wpdb->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', wfDB::networkTable('wfConfig', false)));
		if (is_multisite() && function_exists('update_network_option')) {
			update_network_option(null, self::TABLE_CASE_OPTION, !$hasCamelCaseTable);
			self::$_usingLowercase = !$hasCamelCaseTable;
		}
		else {
			update_option(self::TABLE_CASE_OPTION, !$hasCamelCaseTable);
			self::$_usingLowercase = !$hasCamelCaseTable;
		}
	}
	
	public static function usingLowercase() {
		if (self::$_usingLowercase === null) {
			if (is_multisite() && function_exists('update_network_option')) {
				self::$_usingLowercase = !!get_network_option(null, self::TABLE_CASE_OPTION);
			}
			else {
				self::$_usingLowercase = !!get_option(self::TABLE_CASE_OPTION);
			}
		}
		return self::$_usingLowercase;
	}
}