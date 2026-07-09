<?php

namespace Wordfence\MmdbReader;

class ControlByte {

	const TYPE_EXTENDED = 0;
	const TYPE_POINTER = 1;
	const TYPE_UTF8_STRING = 2;
	const TYPE_DOUBLE = 3;
	const TYPE_BYTES = 4;
	const TYPE_UINT16 = 5;
	const TYPE_UINT32 = 6;
	const TYPE_MAP = 7;
	const TYPE_INT32 = 8;
	const TYPE_UINT64 = 9;
	const TYPE_UINT128 = 10;
	const TYPE_ARRAY = 11;
	const TYPE_CONTAINER = 12;
	const TYPE_END_MARKER = 13;
	const TYPE_BOOLEAN = 14;
	const TYPE_FLOAT = 15;

	const EXTENSION_OFFSET = 7;
	const SIZE_MASK = 31;
	const MAX_SINGLE_BYTE_SIZE = 28;

	private $type;
	private $size;

	public function __construct($type, $size) {
		$this->type = $type;
		$this->size = $size;
	}

	public function getType() {
		return $this->type;
	}

	public function getTypeName() {
		return self::mapTypeName($this->getType());
	}

	public function getSize() {
		return $this->size;
	}

	public function is($type) {
		return $this->type === $type;
	}

	public static function consume($handle) {
		$byte = $handle->readByte();
		$type = $byte >> 5;
		if ($type === self::TYPE_EXTENDED)
			$type = $handle->readByte() + self::EXTENSION_OFFSET;
		$size = $byte & self::SIZE_MASK;
		if ($size > self::MAX_SINGLE_BYTE_SIZE) {
			$bytes = $size - self::MAX_SINGLE_BYTE_SIZE;
			switch ($size) {
			case 30:
				$size = 285;
				break;
			case 31:
				$size = 65821;
				break;
			default:
				break;
			}
			$size += IntegerParser::parseUnsigned($handle, $bytes);
		}
		return new self($type, $size);
	}

	public static function mapTypeName($type) {
		switch ($type) {
		case self::TYPE_EXTENDED:
			return 'TYPE_EXTENDED';
		case self::TYPE_POINTER:
			return 'TYPE_POINTER';
		case self::TYPE_UTF8_STRING:
			return 'TYPE_UTF8_STRING';
		case self::TYPE_DOUBLE:
			return 'TYPE_DOUBLE';
		case self::TYPE_BYTES:
			return 'TYPE_BYTES';
		case self::TYPE_UINT16:
			return 'TYPE_UINT16';
		case self::TYPE_UINT32:
			return 'TYPE_UINT32';
		case self::TYPE_MAP:
			return 'TYPE_MAP';
		case self::TYPE_INT32:
			return 'TYPE_INT32';
		case self::TYPE_UINT64:
			return 'TYPE_UINT64';
		case self::TYPE_UINT128:
			return 'TYPE_UINT128';
		case self::TYPE_ARRAY:
			return 'TYPE_ARRAY';
		case self::TYPE_CONTAINER:
			return 'TYPE_CONTAINER';
		case self::TYPE_END_MARKER:
			return 'TYPE_END_MARKER';
		case self::TYPE_BOOLEAN:
			return 'TYPE_BOOLEAN';
		case self::TYPE_FLOAT:
			return 'TYPE_FLOAT';
		default:
			return 'UNKNOWN';
		}
	}

	public function __toString() {
		return sprintf('%s(%d) of size %d', $this->getTypeName(), $this->getType(), $this->getSize());
	}

}