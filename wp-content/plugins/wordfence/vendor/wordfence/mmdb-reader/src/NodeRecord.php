<?php

namespace Wordfence\MmdbReader;

use Wordfence\MmdbReader\Exception\InvalidOperationException;

class NodeRecord {

	private $reader;
	private $value;

	public function __construct($reader, $value) {
		$this->reader = $reader;
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

	public function isNodePointer() {
		return $this->value < $this->reader->getNodeCount();
	}

	public function getNextNode() {
		if (!$this->isNodePointer())
			throw new InvalidOperationException('The next node was requested for a record that is not a node pointer');
		try {
			return $this->reader->read($this->getValue());
		}
		catch (InvalidOperationException $e) {
			throw new FormatException('Invalid node pointer found in database', $e);
		}
	}

	public function isNullPointer() {
		return $this->value === $this->reader->getNodeCount();
	}

	public function isDataPointer() {
		return $this->value > $this->reader->getNodeCount();
	}

	public function getDataAddress() {
		if (!$this->isDataPointer())
			throw new InvalidOperationException('The data address was requested for a record that is not a data pointer');
		return $this->value - $this->reader->getNodeCount() + $this->reader->getSearchTreeSectionSize();
	}

}