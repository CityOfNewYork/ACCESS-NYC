<?php

require_once __DIR__ . '/wfFileUtils.php';
require_once __DIR__ . '/wfScanFileProperties.php';

class wfScanFile {

	private $realPath = null;
	private $wordpressPath = null;

	public function __construct($realPath, $wordpressPath) {
		$this->realPath = $realPath;
		$this->wordpressPath = $wordpressPath;
	}

	public function getRealPath() {
		return $this->realPath;
	}

	public function getWordpressPath() {
		return $this->wordpressPath;
	}

	public function getDisplayPath() {
		if (wfFileUtils::matchPaths($this->realPath, $this->wordpressPath)) {
			return '~/' . $this->getWordpressPath();
		}
		return $this->realPath;
	}

	public function createChild($childPath) {
		return new self(
			wfFileUtils::realPath(wfFileUtils::joinPaths($this->realPath, $childPath)),
			wfFileUtils::joinPaths($this->wordpressPath, $childPath)
		);
	}

	public function __toString() {
		return $this->getRealPath();
	}

	public function initializeProperties() {
		return new wfScanFileProperties($this->realPath, $this->wordpressPath);
	}

}