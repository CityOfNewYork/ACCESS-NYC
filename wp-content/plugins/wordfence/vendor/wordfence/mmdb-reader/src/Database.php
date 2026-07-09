<?php

namespace Wordfence\MmdbReader;

use Wordfence\MmdbReader\Exception\FormatException;
use Wordfence\MmdbReader\Exception\IncompatibleIpVersionException;
use Wordfence\MmdbReader\Exception\IncompatibleVersionException;
use Wordfence\MmdbReader\Exception\InvalidIpAddressException;
use Wordfence\MmdbReader\Exception\IoException;
use Wordfence\MmdbReader\Io\FileHandle;

class Database {

	const SUPPORTED_MAJOR_VERSION = 2;
	const DELIMITER_METADATA = "\xab\xcd\xefMaxMind.com";

	private $handle;
	private $metadata;
	private $nodeSize;
	private $nodeReader;
	private $dataSectionParser;
	private $startingNodes = array();

	/**
	 * Bind to a MaxMind database using the provided handle
	 * @param resource $resource a valid stream resource that can be used to read the database
	 * @param bool $closeAutomtically if true, the provided resource will be closed automatically
	 * @throws MmdbThrowable if an error occurs while interacting with the database
	 */
	protected function __construct($resource, $closeAutomatically) {
		$this->handle = new FileHandle($resource, $closeAutomatically);
		$this->initialize();
	}

	private function initialize() {
		$this->loadMetadata();
	}

	private function loadMetadata() {
		$this->handle->seek(0, SEEK_END);
		$position = $this->handle->locateString(self::DELIMITER_METADATA, FileHandle::DIRECTION_REVERSE, DatabaseMetadata::MAX_LENGTH, true);
		if ($position === null)
			throw new FormatException("Unable to locate metadata in MMDB file");
		$this->metadata = DatabaseMetadata::parse($this->handle);
		if ($this->metadata->getMajorVersion() !== self::SUPPORTED_MAJOR_VERSION)
			throw new IncompatibleVersionException(sprintf('This library only supports parsing version %d of the MMDB format, a version %d database was provided', self::SUPPORTED_MAJOR_VERSION, $this->metadata->getMajorVersion()));
	}

	private function computeNodeSize() {
		$nodeSize = ($this->metadata->getRecordSize() * 2) / 8;
		if (!is_int($nodeSize))
			throw new FormatException("Node size must be an even number of bytes, computed {$this->nodeSize}");
		return $nodeSize;
	}

	private function getNodeReader() {
		if ($this->nodeReader === null)
			$this->nodeReader = new NodeReader($this->handle, $this->computeNodeSize(), $this->metadata->getNodeCount());
		return $this->nodeReader;
	}

	private function getDataSectionParser() {
		if ($this->dataSectionParser === null) {
			$offset = $this->getNodeReader()->getSearchTreeSectionSize() + 16; //16 null bytes separate the two sections
			$this->dataSectionParser = new DataFieldParser($this->handle, $offset);
		}
		return $this->dataSectionParser;
	}

	/**
	 * Retrieve the metadata for this database
	 * @return DatabaseMetadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}

	/**
	 * Search the database for the given IP address
	 * @param IpAddressInterface|string $ip the IP address for which to search
	 *	A human readable (as accepted by inet_pton) or binary (as accepted by inet_ntop) string may be provided or an instance of IpAddressInterface
	 * @return array|null the matched record or null if no record was found
	 * @throws InvalidIpAddressException if $ip is a string that cannot be parsed as a valid IP address
	 * @throws IncompatibleVersionException if the database IP version and the version of the provided IP address are incompatible (specifically, if an IPv6 address is passed and the database only supports IPv4)
	 */
	public function search($ip) {
		if (is_string($ip)) {
			$ip = IpAddress::createFromString($ip);
		}
		elseif (!$ip instanceof IpAddressInterface) {
			throw new InvalidIpAddressException('IP address must be either a human readable string (presentation format), a binary string (network format), or an instance of Wordfence\MmdbReader\IpAddressInterface, received: ' . print_r($ip, true));
		}
		if ($this->metadata->getIpVersion() === IpAddress::TYPE_IPV4 && $ip->getType() === IpAddress::TYPE_IPV6)
			throw new IncompatibleIpVersionException('This database only support IPv4 addresses, but the provided address is an IPv6 address');
		return $this->searchNodes($ip);
	}

	private function resolveStartingNode($type) {
		$node = $this->getNodeReader()->read(0);
		if ($type === IpAddress::TYPE_IPV4 && $this->metadata->getIpVersion() === IpAddress::TYPE_IPV6) {
			$skippedBits = (IpAddress::LENGTH_IPV6 - IpAddress::LENGTH_IPV4) * 8;
			while ($skippedBits-- > 0) {
				$record = $node->getLeft();
				if ($record->isNodePointer()) {
					$node = $record->getNextNode();
				}
				else {
					return $record;
				}
			}
		}
		return $node;
	}

	private function getStartingNode($type) {
		if (!array_key_exists($type, $this->startingNodes)) {
			$this->startingNodes[$type] = $this->resolveStartingNode($type);
		}
		return $this->startingNodes[$type];
	}

	private function searchNodes($ip) {
		$key = $ip->getBinary();
		$byteCount = strlen($key);
		$nodeReader = $this->getNodeReader();
		$node = $this->getStartingNode($ip->getType());
		$bits = '';
		$record = null;
		if ($node instanceof Node) {
			for ($byteIndex = 0; $byteIndex < $byteCount; $byteIndex++) {
				$byte = ord($key[$byteIndex]);
				for ($bitOffset = 7; $bitOffset >= 0; $bitOffset--) {
					$bit = ($byte >> $bitOffset) & 1;
					$record = $node->getRecord($bit);
					if ($record->isNodePointer()) {
						$node = $record->getNextNode();
					}
					else {
						break 2;
					}
				}
			}
		}
		else {
			$record = $node;
		}
		if ($record->isNullPointer()) {
			return null;
		}
		elseif ($record->isDataPointer()) {
			$this->handle->seek($record->getDataAddress(), SEEK_SET);
			$data = $this->getDataSectionParser()->parseField();
			return $data;
		}
		else {
			return null;
		}
	}

	/**
	 * Open the MMDB file at the given path
	 * @param string $path the path of an MMDB file
	 * @throws IoException if unable to open the file at the provided path
	 * @throws MmdbThrowable if an error occurs while initializing the database
	 */
	public static function open($path) {
		$handle = fopen($path, 'rb');
		if ($handle === false)
			throw new IoException("Unable to open MMDB file at {$path}");
		return new self($handle, true);
	}

}