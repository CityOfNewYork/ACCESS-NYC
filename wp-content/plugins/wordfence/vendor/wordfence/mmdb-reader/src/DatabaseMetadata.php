<?php

namespace Wordfence\MmdbReader;

use Wordfence\MmdbReader\Exception\FormatException;

class DatabaseMetadata {

	const MAX_LENGTH = 131072; //128 * 1024;

	const FIELD_MAJOR_VERSION = 'binary_format_major_version';
	const FIELD_NODE_COUNT = 'node_count';
	const FIELD_RECORD_SIZE = 'record_size';
	const FIELD_IP_VERSION = 'ip_version';
	const FIELD_BUILD_EPOCH = 'build_epoch';

	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	private function getField($key, $default = null, &$exists = null) {
		if (!array_key_exists($key, $this->data)) {
			$exists = false;
			return $default;
		}
		$exists = true;
		return $this->data[$key];
	}

	private function requireField($key) {
		$value = $this->getField($key, null, $exists);
		if (!$exists)
			throw new FormatException("Metadata field {$key} is missing");
		return $value;
	}

	public function requireInteger($key) {
		$value = $this->requireField($key);
		if (!is_int($value))
			throw new FormatException("Field {$key} should be an integer, received: " . print_r($value, true));
		return $value;
	}

	public function getMajorVersion() {
		return $this->requireInteger(self::FIELD_MAJOR_VERSION);
	}

	public function getNodeCount() {
		return $this->requireInteger(self::FIELD_NODE_COUNT);
	}

	public function getRecordSize() {
		return $this->requireInteger(self::FIELD_RECORD_SIZE);
	}

	public function getIpVersion() {
		return $this->requireInteger(self::FIELD_IP_VERSION);
	}

	public function getBuildEpoch() {
		return $this->requireInteger(self::FIELD_BUILD_EPOCH);
	}

	public static function parse($handle) {
		$offset = $handle->getPosition();
		$parser = new DataFieldParser($handle, $offset);
		$value = $parser->parseField();
		if (!is_array($value))
			throw new FormatException('Unexpected field type found when metadata map was expected: ' . print_r($value, true));
		return new self($value);
	}

}