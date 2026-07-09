<?php

namespace Wordfence\MmdbReader;

use Wordfence\MmdbReader\Exception\FormatException;
use Wordfence\MmdbReader\Exception\InvalidArgumentException;

class DataFieldParser {

	private $handle;
	private $sectionOffset;

	public function __construct($handle, $sectionOffset = null) {
		$this->handle = $handle;
		$this->sectionOffset = $sectionOffset === null ? $this->handle->getPosition() : $sectionOffset;
	}

	public function processControlByte() {
		return ControlByte::consume($this->handle);
	}

	private function readStandardField($controlByte) {
		$size = $controlByte->getSize();
		if ($size === 0)
			return '';
		return $this->handle->read($size);
	}

	private function parseUtf8String($controlByte) {
		return $this->readStandardField($controlByte);
	}

	private function parseUnsignedInteger($controlByte) {
		//TODO: Does this handle large-enough values gracefully?
		return IntegerParser::parseUnsigned($this->handle, $controlByte->getSize());
	}

	private function parseMap($controlByte) {
		$map = array();
		for ($i = 0; $i < $controlByte->getSize(); $i++) {
			$keyByte = $this->processControlByte();
			$key = $this->parseField($keyByte);
			if (!is_string($key))
				throw new FormatException('Map keys must be strings, received ' . $keyByte . ' / ' . print_r($key, true) . ', map: ' . print_r($map, true));
			$value = $this->parseField();
			$map[$key] = $value;
		}
		return $map;
	}

	private function parseArray($controlByte) {
		$array = array();
		for ($i = 0; $i < $controlByte->getSize(); $i++) {
			$array[$i] = $this->parseField();
		}
		return $array;
	}

	private function parseBoolean($controlByte) {
		return (bool) $controlByte->getSize();
	}

	private static function unpackSingleValue($format, $data, $controlByte) {
		$values = unpack($format, $data);
		if ($values === false)
			throw new FormatException("Unpacking field failed for {$controlByte}");
		return reset($values);
	}

	private static function getPackedLength($formatCharacter) {
		switch ($formatCharacter) {
		case 'E':
			return 8;
		case 'G':
		case 'l':
			return 4;
		}
		throw new InvalidArgumentException("Unsupported format character: {$formatCharacter}");
	}

	private static function usesSystemByteOrder($formatCharacter) {
		switch ($formatCharacter) {
		case 'l':
			return true;
		default:
			return false;
		}
	}

	private function parseByUnpacking($controlByte, $format) {
		//TODO: Is this reliable for float/double types, considering that the size for unpack is platform dependent?
		$data = $this->readStandardField($controlByte);
		$data = str_pad($data, self::getPackedLength($format), "\0", STR_PAD_LEFT);
		if (self::usesSystemByteOrder($format))
			$data = Endianness::convert($data, Endianness::BIG);
		return $this->unpackSingleValue($format, $data, $controlByte);
	}

	private function parsePointer($controlByte) {
		$data = $controlByte->getSize();
		$size = $data >> 3;
		$address = $data & 7;
		if ($size === 3)
			$address = 0;
		for ($i = 0; $i < $size + 1; $i++) {
			$address = ($address << 8) + $this->handle->readByte();
		}
		switch ($size) {
		case 1:
			$address += 2048;
			break;
		case 2:
			$address += 526336;
			break;
		}
		$previous = $this->handle->getPosition();
		$this->handle->seek($this->sectionOffset + $address, SEEK_SET);
		$referenceControlByte = $this->processControlByte();
		if ($referenceControlByte->getType() === ControlByte::TYPE_POINTER)
			throw new FormatException('Per the MMDB specification, pointers may not point to other pointers. This database does not comply with the specification.');
		$value = $this->parseField($referenceControlByte);
		$this->handle->seek($previous, SEEK_SET);
		return $value;
	}

	private function parseSignedInteger($controlByte, $format) {
		if ($controlByte->getSize() === 0)
			return 0;
		return $this->parseByUnpacking($controlByte, $format);
	}

	public function parseField(&$controlByte = null) {
		if ($controlByte === null)
			$controlByte = $this->processControlByte();
		switch ($controlByte->getType()) {
		case ControlByte::TYPE_POINTER:
			return $this->parsePointer($controlByte);
		case ControlByte::TYPE_UTF8_STRING:
			return $this->parseUtf8String($controlByte);
		case ControlByte::TYPE_DOUBLE:
			$this->parseByUnpacking($controlByte, 'E');
		case ControlByte::TYPE_BYTES:
		case ControlByte::TYPE_CONTAINER:
			return $this->readStandardField($controlByte);
		case ControlByte::TYPE_UINT16:
		case ControlByte::TYPE_UINT32:
		case ControlByte::TYPE_UINT64:
		case ControlByte::TYPE_UINT128:
			return $this->parseUnsignedInteger($controlByte);
		case ControlByte::TYPE_INT32:
			return $this->parseSignedInteger($controlByte, 'l');
		case ControlByte::TYPE_MAP:
			return $this->parseMap($controlByte);
		case ControlByte::TYPE_ARRAY:
			return $this->parseArray($controlByte);
		case ControlByte::TYPE_END_MARKER:
			return null;
		case ControlByte::TYPE_BOOLEAN:
			return $this->parseBoolean($controlByte);
		case ControlByte::TYPE_FLOAT:
			$this->parseByUnpacking($controlByte, 'G');
		default:
			throw new FormatException("Unable to parse data field for {$controlByte}");
		}
	}

}