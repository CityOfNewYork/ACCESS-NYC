<?php

require_once __DIR__ . '/wfFileUtils.php';
require_once __DIR__ . '/wfScanFile.php';
require_once __DIR__ . '/wfScanFileLink.php';

class wfScanPath {

	private $baseDirectory;
	private $path;
	private $realPath;
	private $wordpressPath;
	private $expectedFiles;

	public function __construct($baseDirectory, $path, $wordpressPath = null, $expectedFiles = null) {
		$this->baseDirectory = $baseDirectory;
		$this->path = $path;
		$this->realPath = wfFileUtils::realPath($path);
		$this->wordpressPath = $wordpressPath;
		$this->expectedFiles = is_array($expectedFiles) ? array_flip($expectedFiles) : null;
	}

	public function getPath() {
		return $this->path;
	}

	public function getRealPath() {
		return $this->realPath;
	}

	public function getWordpressPath() {
		return $this->wordpressPath;
	}

	public function hasExpectedFiles() {
		return $this->expectedFiles !== null && !empty($this->expectedFiles);
	}

	public function expectsFile($name) {
		return array_key_exists($name, $this->expectedFiles);
	}

	public function isBaseDirectory() {
		return $this->path === $this->baseDirectory;
	}

	public function isBelowBaseDirectory() {
		return wfFileUtils::belongsTo($this->path, $this->baseDirectory);
	}

	public function getContents() {
		return wfFileUtils::getContents($this->realPath);
	}

	public function createScanFile($relativePath) {
		$path = wfFileUtils::joinPaths($this->realPath, $relativePath);
		$realPath = wfFileUtils::realPath($path);
		$wordpressPath = wfFileUtils::trimSeparators(wfFileUtils::joinPaths($this->wordpressPath, $relativePath), true, false);
		if (is_link($path)) {
			return new wfScanFileLink($path, $realPath, $wordpressPath);
		}
		else {
			return new wfScanFile($realPath, $wordpressPath);
		}
	}

	public function __toString() {
		return $this->realPath;
	}

}