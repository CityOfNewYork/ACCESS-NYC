<?php

namespace Wordfence\MmdbReader;

class NodeReader {

	const SHARED_MASK_LEFT = 240; //0b11110000
	const SHARED_MASK_RIGHT = 15; //0b00001111

	private $handle;
	private $nodeSize, $nodeCount;
	private $searchTreeSectionSize;
	private $recordWholeBytes, $recordBits;
	private $sharedByteOffset;

	public function __construct($handle, $nodeSize, $nodeCount) {
		$this->handle = $handle;
		$this->nodeSize = $nodeSize;
		$this->nodeCount = $nodeCount;
		$this->searchTreeSectionSize = $nodeSize * $nodeCount;
		$this->computeRecordSizes();
	}

	private function computeRecordSizes() {
		$this->recordWholeBytes = (int) ($this->nodeSize / 2);
		$this->recordBits = $this->nodeSize % 2;
		if ($this->recordBits > 0)
			$this->sharedByteOffset = $this->recordWholeBytes + 1;
	}

	public function read($position = 0) {
		if ($position > $this->nodeCount)
			throw new InvalidOperationException("Read requested for node at {$position}, but only {$this->nodeCount} nodes are present");
		$offset = $position * $this->nodeSize;
		$this->handle->seek($offset, SEEK_SET);
		$data = $this->handle->read($this->nodeSize);
		return new Node($this, $data);
	}

	private function hasSharedByte() {
		return $this->sharedByteOffset !== null;
	}

	private function getWholeByteOffset($side) {
		return $side === Node::SIDE_LEFT ? 0 : ($this->hasSharedByte() ? $this->sharedByteOffset : $this->recordWholeBytes);
	}

	public function extractRecord($nodeData, $side) {
		if ($this->hasSharedByte()) {
			$sharedByte = ord($nodeData[$this->sharedByteOffset]);
			if ($side === Node::SIDE_LEFT) {
				$value = $sharedByte >> 4;
			}
			else {
				$value = $sharedByte & self::SHARED_MASK_RIGHT;
			}
		}
		else {
			$value = 0;
		}
		$offset = $this->getWholeByteOffset($side);
		$end = $offset + $this->recordWholeBytes;
		for ($i = $offset; $i < $end; $i++) {
			$byte = ord($nodeData[$i]);
			$value = ($value << 8) | $byte;
		}
		return $value;
	}

	public function getNodeCount() {
		return $this->nodeCount;
	}

	public function getSearchTreeSectionSize() {
		return $this->searchTreeSectionSize;
	}

}