<?php

namespace Wordfence\MmdbReader\Io;

use Wordfence\MmdbReader\Exception\IoException;

class FileHandle {

	const POSITION_START = 0;

	const DIRECTION_FORWARD = 1;
	const DIRECTION_REVERSE = -1;

	const CHUNK_SIZE_DEFAULT = 1024;

	private $resource;
	private $close;

	public function __construct($resource, $close = true) {
		$this->resource = $resource;
		$this->close = $close;
	}

	public function __destruct() {
		if ($this->close)
			fclose($this->resource);
	}

	public function seek($offset, $whence = SEEK_SET) {
		if (fseek($this->resource, $offset, $whence) !== 0)
			throw new IoException("Seeking file to offset {$offset} failed");
	}

	public function getPosition() {
		$position = ftell($this->resource);
		if ($position === false)
			throw new IoException('Retrieving current position in file failed');
		return $position;
	}

	public function isAtStart() {
		return $this->getPosition() === self::POSITION_START;
	}

	public function isAtEnd() {
		return feof($this->resource);
	}

	public function read($length) {
		$read = fread($this->resource, $length);
		if ($read === false)
			throw new IoException("Reading {$length} byte(s) from file failed");
		return $read;
	}

	public function readByte() {
		return ord($this->read(1));
	}

	public function readAll($chunkSize = self::CHUNK_SIZE_DEFAULT) {
		$data = '';
		do {
			$chunk = $this->read($chunkSize);
			if (empty($chunk))
				break;
			$data .= $chunk;
		} while (true);
		return $data;
	}

	public function locateString($string, $direction, $limit = null, $after = false) {
		$searchStart = $limit === null ? null : $this->getPosition();
		$length = strlen($string);
		$position = $searchStart;
		if ($direction === self::DIRECTION_REVERSE)
			$position -= $length;
		do {
			try {
				$this->seek($position, SEEK_SET);
			}
			catch (IoException $e) {
				//This assumes that a seek failure means that the target position is out of range (and hence the search just needs to stop rather than throwing an exception)
				break;
			}
			$test = $this->read($length);
			if ($test === $string) {
				return $position + ($after ? $length : 0);
			}
			$position += $direction;
		} while ($limit === null || abs($position - $searchStart) < $limit);
		return null;
	}

}