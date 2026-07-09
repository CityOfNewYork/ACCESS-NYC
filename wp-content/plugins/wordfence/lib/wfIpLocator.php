<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/wfIpLocation.php';

use Wordfence\MmdbReader\Database;
use Wordfence\MmdbReader\Exception\MmdbThrowable;

class wfIpLocator {

	const SOURCE_BUNDLED = 0;
	const SOURCE_WFLOGS = 1;

	private static $instances = array();

	private $database;
	private $preferred;

	private function __construct($database, $preferred) {
		$this->database = $database;
		$this->preferred = $preferred;
	}

	public function isPreferred() {
		return $this->preferred;
	}

	private static function logError($message) {
		if (class_exists('wfUtils'))
			wfUtils::check_and_log_last_error('ip_locator', 'IP Location Error:', $message, 0);
	}

	public function locate($ip) {
		if ($this->database !== null) {
			try {
				$record = $this->database->search($ip);
				if ($record !== null)
					return new wfIpLocation($record);
			}
			catch (MmdbThrowable $t) {
				self::logError('Failed to locate IP address: ' . $t->getMessage());
			}
		}
		return null;
	}

	public function getCountryCode($ip, $default = '') {
		$record = $this->locate($ip);
		if ($record !== null)
			return $record->getCountryCode();
		return $default;
	}

	public function getDatabaseVersion() {
		if ($this->database !== null) {
			try {
				return $this->database->getMetadata()->getBuildEpoch();
			}
			catch (MmdbThrowable $t) {
				self::logError('Failed to retrieve database version: ' . $t->getMessage());
			}
		}
		return null;
	}

	private static function getDatabaseDirectory($source) {
		switch ($source) {
		case self::SOURCE_BUNDLED:
			return WFWAF_LOG_PATH;
		case self::SOURCE_BUNDLED:
		default:
			return __DIR__;
		}
	}

	private static function initializeDatabase($preferredSource, &$isPreferred) {
		$sources = array();
		if ($preferredSource !== self::SOURCE_BUNDLED)
			$sources[] = $preferredSource;
		$sources[] = self::SOURCE_BUNDLED;
		$isPreferred = true;
		foreach ($sources as $source) {
			$directory = self::getDatabaseDirectory($source);
			try {
				$path = $directory . '/' . wfIpLocation::DATABASE_FILE_NAME;
				if (file_exists($path)) //Preemptive check to prevent warnings
					return Database::open($path);
			}
			catch (MmdbThrowable $t) {
				self::logError('Failed to initialize IP location database: ' . $t->getMessage());
			}
			$preferred = false;
		}
		return null;
	}

	public static function getInstance($preferredSource = null) {
		if ($preferredSource === null)
			$preferredSource = self::SOURCE_WFLOGS;
		if (!array_key_exists($preferredSource, self::$instances)) {
			$database = self::initializeDatabase($preferredSource, $isPreferred);
			self::$instances[$preferredSource] = new wfIpLocator($database, $isPreferred);
		}
		return self::$instances[$preferredSource];
	}

}