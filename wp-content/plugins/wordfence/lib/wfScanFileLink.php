<?php

require_once __DIR__ . '/wfScanFile.php';

class wfScanFileLink extends wfScanFile {

	private $linkPath;

	public function __construct($linkPath, $realPath, $wordpressPath) {
		parent::__construct($realPath, $wordpressPath);
		$this->linkPath = $linkPath;
	}

	public function getLinkPath() {
		return $this->linkPath;
	}

	public function getDisplayPath() {
		return $this->getLinkPath();
	}

}