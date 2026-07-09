<?php

namespace Wordfence\MmdbReader;

class Node {

	const SIDE_LEFT = 0;
	const SIDE_RIGHT = 1;

	private $reader;
	private $data;
	private $left, $right;

	public function __construct($reader, $data) {
		$this->reader = $reader;
		$this->data = $data;
	}

	public function getRecord($side) {
		$value = $this->reader->extractRecord($this->data, $side);
		return new NodeRecord($this->reader, $value);
	}

	public function getLeft() {
		return $this->getRecord(self::SIDE_LEFT);
	}

	public function getRight() {
		return $this->getRecord(self::SIDE_RIGHT);
	}

}